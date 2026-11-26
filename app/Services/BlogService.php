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
    public function save(array $data): array {
        if (!is_dir($this->postsDir)&&!mkdir($this->postsDir,0775,true)&&!is_dir($this->postsDir)) throw new \RuntimeException('Article storage unavailable.');
        $slug = $data['slug'] ?? '';
        if (!is_string($slug)) throw new \InvalidArgumentException('Invalid article slug.');
        if ($slug === '') $slug = $this->slugify((string)($data['title'] ?? 'untitled'));
        if (!self::validSlug($slug)) throw new \InvalidArgumentException('Invalid article slug.');
        $data['slug']=$slug;
        $existing=$this->find($slug);
        if (is_file($this->postsDir.'/'.$slug.'.md')&&$existing===null) throw new \RuntimeException('Existing article metadata is invalid. Repair the source before editing.');
        $flag=$data['published']??($existing['published']??true);
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
        if (is_link($destination)) throw new \RuntimeException('Article destination unavailable.');
        $tmp=tempnam($this->postsDir,'.blog-');
        if ($tmp===false) throw new \RuntimeException('Article storage unavailable.');
        try {
            if (file_put_contents($tmp,$md,LOCK_EX)!==strlen($md)||!rename($tmp,$destination)) throw new \RuntimeException('Article save failed.');
        } finally { if (is_file($tmp)) unlink($tmp); }
        return $data;
    }
    public function delete(string $slug): void {
        if (!self::validSlug($slug)) throw new \InvalidArgumentException('Invalid article slug.');
        $file = $this->postsDir . '/' . $slug . '.md';
        if (is_file($file)&&!is_link($file)) unlink($file);
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
        $raw = file_get_contents($file);
        if ($raw === false || $raw === '') return null;
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
        $meta['slug'] = $meta['slug'] ?? pathinfo($file, PATHINFO_FILENAME);
        if (!is_string($meta['slug'])||!self::validSlug($meta['slug'])||$meta['slug']!==pathinfo($file,PATHINFO_FILENAME)) return null;
        $meta['published'] = $meta['published'] ?? true;
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
