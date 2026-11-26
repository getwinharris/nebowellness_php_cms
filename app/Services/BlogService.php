<?php
namespace App\Services;
use App\Services\MarkdownRenderer;
final class BlogService {
    private string $postsDir;
    private string $categoriesFile;
    public function __construct(?string $postsDir=null) {
        $this->postsDir = $postsDir??app_path('content/blog/posts');
        $this->categoriesFile = app_path('content/blog/categories.yaml');
    }
    /** Categories that disappear with their module, so their posts disappear too. */
    private const MODULE_CATEGORIES = [
        'shop' => ['shop', 'products', 'orders'],
    ];

    /**
     * @param bool $all true for the admin, which must see hidden posts to unhide them.
     *
     * A post whose category belongs to a switched-off module is withheld from the site.
     */
    public function all(bool $all = false,?array $modules=null): array {
        $strict=$modules!==null;
        if ($strict&&(!is_dir($this->postsDir)||!is_readable($this->postsDir)||is_link($this->postsDir))) throw new \RuntimeException('Article source unavailable.');
        if ($strict&&!$all&&empty($modules['blog'])) return [];
        $files=glob($this->postsDir.'/*.md');
        if ($strict&&($files===false||count($files)>500)) throw new \RuntimeException('Article inventory unavailable or exceeds budget.');
        $posts = [];
        foreach ($files ?: [] as $file) {
            if (!$strict&&is_link($file)) continue;
            if ($strict&&(is_link($file)||!is_file($file)||!is_readable($file)||filesize($file)>524288)) throw new \RuntimeException('Article source unavailable or exceeds budget.');
            $post = $this->parseFile($file);
            if (!$post) {
                if ($strict) throw new \RuntimeException('Article source unavailable.');
                continue;
            }
            if (!$all) {
                if (!$this->isPublic($post,$modules)) continue;
                if ($strict&&(!is_string($post['slug']??null)||$post['slug']!==pathinfo($file,PATHINFO_FILENAME))) throw new \RuntimeException('Article slug does not resolve to its source.');
            }
            if (!$all) unset($post['_revision']);
            $posts[] = $post;
        }
        usort($posts, fn($a, $b) => strcmp($b['published_at'] ?? '', $a['published_at'] ?? ''));
        return $posts;
    }

    /** Date-only editorial schedules use the configured application timezone.
     * Eligibility is calculated on every read, without a cron worker or DB job. */
    public static function publicationState(array $post, ?\DateTimeImmutable $now=null): string {
        if (($post['published'] ?? true) === false) return 'hidden';
        if (($post['published'] ?? true) !== true) return 'invalid';
        $date=$post['published_at'] ?? '';
        if ($date==='') return 'visible'; // Historical undated Markdown.
        if (!self::validDate($date)) return 'invalid';
        $today=($now??new \DateTimeImmutable('now'))->setTimezone(new \DateTimeZone(date_default_timezone_get()))->format('Y-m-d');
        return $date>$today?'scheduled':'visible';
    }
    public static function validDate(mixed $date): bool {
        return is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)===1
            && checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4));
    }
    public function isPublic(array $post,?array $modules=null,?\DateTimeImmutable $now=null): bool {
        return ($modules===null||!empty($modules['blog'])) && self::publicationState($post,$now)==='visible'
            && !$this->categoryIsDisabled((string)($post['category']??''),$modules);
    }
    public static function validSlug(string $slug): bool {
        return preg_match('/^[a-z0-9][a-z0-9-]{0,119}$/D',$slug)===1;
    }
    /** Current observed outcomes, not a claim that background jobs ran. */
    public function publicationReport(): array {
        $counts=['visible'=>0,'hidden'=>0,'scheduled'=>0,'invalid'=>0];
        $next=null;
        foreach ($this->all(true) as $post) {
            $state=self::publicationState($post);
            $counts[$state]++;
            if ($state==='scheduled' && ($next===null || $post['published_at']<$next)) $next=$post['published_at'];
        }
        return ['mode'=>'date-gated-read','timezone'=>date_default_timezone_get(),'counts'=>$counts,'next_date'=>$next];
    }

    public function categoryIsDisabled(string $category,?array $modules=null): bool {
        $category = strtolower(trim($category));
        if ($category === '') return false;
        foreach (self::MODULE_CATEGORIES as $module => $categories) {
            if (in_array($category, $categories, true) && !($modules===null?module_on($module):!empty($modules[$module]))) return true;
        }
        return false;
    }
    public function find(string $slug): ?array {
        if (!self::validSlug($slug)) return null;
        $file = $this->postsDir . '/' . $slug . '.md';
        return is_file($file) && !is_link($file) ? $this->parseFile($file) : null;
    }
    public function categories(): array {
        if (!is_file($this->categoriesFile)) return [];
        $yaml = @file_get_contents($this->categoriesFile);
        if ($yaml === false || $yaml === '') return [];
        return $this->parseYamlList($yaml);
    }
    public function save(array $data,string $expectedRevision): array {
        if (!is_dir($this->postsDir)&&!mkdir($this->postsDir,0775,true)&&!is_dir($this->postsDir)) throw new \RuntimeException('Article storage unavailable.');
        $slug = $data['slug'] ?? '';
        if (!is_string($slug)) throw new \InvalidArgumentException('Invalid article slug.');
        if ($slug === '') {
            if(!is_string($data['title']??null))throw new \InvalidArgumentException('Enter an article title.');
            $slug=$this->slugify($data['title']);
        }
        if (!self::validSlug($slug)) throw new \InvalidArgumentException('Invalid article slug.');
        $history=new BlogRevisionStore($this->postsDir);
        return $history->locked(fn()=> $this->saveLocked($history,$slug,$data,$expectedRevision));
    }
    private function checkedSource(string $slug,string $expectedRevision): ?string {
        if(!self::validSlug($slug))throw new \InvalidArgumentException('Invalid article slug.');
        if($expectedRevision!=='new'&&!BlogRevisionStore::hashValid($expectedRevision))throw new \InvalidArgumentException('Reload the article before saving.');
        $path=$this->postsDir.'/'.$slug.'.md';
        if(!file_exists($path)&&!is_link($path)){
            if($expectedRevision!=='new')throw new \DomainException('Article no longer exists. Reload the editor.');
            return null;
        }
        $bytes=BlogRevisionStore::read($path);
        if($expectedRevision==='new'||!hash_equals($expectedRevision,hash('sha256',$bytes)))throw new \DomainException('Article changed or the slug already exists. Reload before saving.');
        return $bytes;
    }
    private function saveLocked(BlogRevisionStore $history,string $slug,array $data,string $expectedRevision): array {
        $before=$this->checkedSource($slug,$expectedRevision);
        $data['slug']=$slug;
        $existing=$before===null?null:$this->parseBytes($before,$slug);
        if($before===null&&$history->inventory($slug)!==[])throw new \DomainException('This slug has retained article history. Choose a new slug.');

        if ($before!==null&&$existing===null) throw new \RuntimeException('Existing article metadata is invalid. Repair the source before editing.');
        $data=array_replace($existing??[],$data);
        if(!is_string($data['title']??null)||trim($data['title'])==='')throw new \InvalidArgumentException('Enter an article title.');
        $flag=$data['published']??true;
        if (!in_array($flag,[true,false,'true','false','1','0'],true)) throw new \InvalidArgumentException('Invalid publication flag.');
        $data['published']=in_array($flag,[true,'true','1'],true);
        $data['published_at']=$data['published_at']??($existing['published_at']??date('Y-m-d'));
        if ($data['published_at']!==''&&!self::validDate($data['published_at'])) throw new \InvalidArgumentException('Use a valid publication date (YYYY-MM-DD).');
        $content = $data['content'] ?? '';
        if (!is_string($content)) throw new \InvalidArgumentException('Invalid article content.');
        unset($data['content']);
        $frontmatter = '';
        foreach (['title','slug','type','category','published','published_at','updated_at','summary','order','excerpt','author','seo_title','seo_description','keywords','og_image','image_alt','source_url','template'] as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                $val = $data[$key];
                if (is_bool($val)) $val = $val ? 'true' : 'false';
                elseif (!is_string($val)&&!is_int($val)&&!is_float($val)) throw new \InvalidArgumentException('Invalid article metadata.');
                elseif (is_string($val)) {
                    if (preg_match('/[\r\n]/',$val)) throw new \InvalidArgumentException('Article metadata must be on one line.');
                    if (preg_match('/[:#"\\\\]/',$val)||trim($val)!==$val||in_array($val,['true','false','null'],true)||is_numeric($val)) {
                        $val=json_encode($val,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
                    }
                }
                $frontmatter .= $key . ': ' . $val . "\n";
            }
        }
        $md = "---\n{$frontmatter}---\n\n{$content}";
        $destination=$this->postsDir.'/'.$slug.'.md';
        if($before!==$md){
            if($before!==null)$history->snapshot($slug,$before);
            // Recheck the actual bytes immediately before replacing. Manual
            // file editors/deployment are not participants in this lock.
            $this->checkedSource($slug,$expectedRevision);
            BlogRevisionStore::atomicWrite($destination,$md,0644);
        }
        $data['content']=$content;
        unset($data['html']);
        $data['_revision']=hash('sha256',$md);
        $data['_created']=$before===null;
        return $data;
    }
    public function revisions(string $slug): array {
        $history=new BlogRevisionStore($this->postsDir);
        return $history->locked(fn(bool $ready)=>$ready?$history->inventory($slug):[],false);
    }
    public function revisionPreview(string $slug,string $revision): array {
        $history=new BlogRevisionStore($this->postsDir);
        return $history->locked(function()use($history,$slug,$revision):array {
            $post=$this->parseBytes($history->load($slug,$revision),$slug);
            if($post===null)throw new \RuntimeException('Article revision metadata invalid.');
            return ['revision'=>$revision,'title'=>(string)($post['title']??''),'content'=>(string)$post['content']];
        },false);
    }
    /** Restore editorial content only, not publication state or article identity. */
    public function restore(string $slug,string $revision,string $expectedRevision): array {
        $history=new BlogRevisionStore($this->postsDir);
        return $history->locked(function()use($history,$slug,$revision,$expectedRevision):array{
            $before=$this->checkedSource($slug,$expectedRevision);
            if($before===null)throw new \DomainException('Restore requires an existing article.');
            $current=$this->parseBytes($before,$slug);
            $prior=$this->parseBytes($history->load($slug,$revision),$slug);
            if($current===null||$prior===null)throw new \RuntimeException('Article revision metadata invalid.');
            // Clear fields absent from the selected historical version rather
            // than silently retaining newer editorial values.
            foreach(array_keys($current) as $key)if(!array_key_exists($key,$prior))$prior[$key]='';
            foreach(['published','published_at','category','type'] as $key)$prior[$key]=$current[$key]??($key==='published'?true:'');
            $prior['updated_at']=date('Y-m-d');
            return $this->saveLocked($history,$slug,$prior,$expectedRevision);
        });
    }
    public function toggle(string $slug,string $expectedRevision): array {
        $history=new BlogRevisionStore($this->postsDir);
        return $history->locked(function()use($history,$slug,$expectedRevision):array{
            $bytes=$this->checkedSource($slug,$expectedRevision);
            if($bytes===null||($post=$this->parseBytes($bytes,$slug))===null)throw new \DomainException('Article unavailable.');
            $post['published']=!($post['published']===true);
            $post['updated_at']=date('Y-m-d');
            return $this->saveLocked($history,$slug,$post,$expectedRevision);
        });
    }
    public function delete(string $slug,string $expectedRevision): void {
        $history=new BlogRevisionStore($this->postsDir);
        $history->locked(function()use($history,$slug,$expectedRevision):void{
            $bytes=$this->checkedSource($slug,$expectedRevision);
            if($bytes===null)throw new \DomainException('Article not found.');
            $history->snapshot($slug,$bytes);
            $this->checkedSource($slug,$expectedRevision);
            if(!unlink($this->postsDir.'/'.$slug.'.md'))throw new \RuntimeException('Article deletion could not be confirmed.');
        });
    }
    public function saveCategory(array $data): void {
        $cats = $this->categories();
        $found = false;
        foreach ($cats as &$c) {
            if (($c['slug'] ?? '') === ($data['slug'] ?? '')) { $c = $data; $found = true; break; }
        }
        if (!$found) $cats[] = $data;
        $yaml = "categories:\n";
        foreach ($cats as $c) {
            $yaml .= "  - slug: " . ($c['slug'] ?? '') . "\n";
            $yaml .= "    name: " . ($c['name'] ?? '') . "\n";
            $yaml .= "    description: " . ($c['description'] ?? '') . "\n";
        }
        file_put_contents($this->categoriesFile, $yaml, LOCK_EX);
    }
    public function deleteCategory(string $slug): void {
        $cats = array_values(array_filter($this->categories(), fn($c) => ($c['slug'] ?? '') !== $slug));
        $yaml = "categories:\n";
        foreach ($cats as $c) {
            $yaml .= "  - slug: " . ($c['slug'] ?? '') . "\n";
            $yaml .= "    name: " . ($c['name'] ?? '') . "\n";
            $yaml .= "    description: " . ($c['description'] ?? '') . "\n";
        }
        file_put_contents($this->categoriesFile, $yaml, LOCK_EX);
    }
    private function parseFile(string $file): ?array {
        try {$raw=BlogRevisionStore::read($file);}catch(\Throwable){return null;}
        return $this->parseBytes($raw,pathinfo($file,PATHINFO_FILENAME));
    }
    private function parseBytes(string $raw,string $slug): ?array {
        $meta = [];
        $content = $raw;
        if (str_starts_with($raw, '---')) {
            if (!preg_match('/\A---\R(.*?)\R---(?:\R|$)(.*)\z/s',$raw,$parts)) return null;
            {
                $front = trim($parts[1]);
                $content = trim($parts[2]);
                foreach (explode("\n", $front) as $line) {
                    $line = trim($line);
                    if ($line === '' || !str_contains($line, ':')) continue;
                    [$k, $v] = explode(':', $line, 2);
                    $k = trim($k); $v = trim($v);
                    if (array_key_exists($k,$meta)) return null;
                    if (str_starts_with($v,'"') && str_ends_with($v,'"')) {
                        $decoded=json_decode($v,true);
                        $v=is_string($decoded)?$decoded:trim($v,'"');
                    } elseif ($v === 'true') $v = true; elseif ($v === 'false') $v = false;
                    elseif (preg_match('/^\d{4}-\d{2}-\d{2}/', $v)) $v = $v;
                    elseif (is_numeric($v)) $v = (float)$v;
                    else { $v = trim($v, '"\'"'); }
                    $meta[$k] = $v;
                }
            }
        }
        $meta['content'] = $content;
        $meta['slug'] = $meta['slug'] ?? $slug;
        if (!is_string($meta['slug'])||!self::validSlug($meta['slug'])||$meta['slug']!==$slug) return null;
        $meta['published'] = $meta['published'] ?? true;
        $meta['_revision']=hash('sha256',$raw);
        $meta['html'] = (new MarkdownRenderer())->render($content);
        return $meta;
    }
    private function parseYamlList(string $yaml): array {
        $items = [];
        $current = null;
        foreach (explode("\n", $yaml) as $line) {
            if (preg_match('/^\s*-\s*slug:\s*(.+)$/', $line, $m)) {
                if ($current) $items[] = $current;
                $current = ['slug' => trim($m[1])];
            } elseif ($current && preg_match('/^\s+(name|description):\s*(.+)$/', $line, $m)) {
                $current[$m[1]] = trim($m[2]);
            }
        }
        if ($current) $items[] = $current;
        return $items;
    }
    private function slugify(string $text): string {
        return trim(preg_replace('/[^a-z0-9-]+/', '-', strtolower(preg_replace('/[^\w\s-]/u', '', $text))), '-');
    }
}
