<?php
declare(strict_types=1);
namespace App\Services;

/** Immutable slug, versioned authored document, explicit owner publication. */
final class CmsPageService {
    public const VERSION=1;
    public function __construct(private DatabaseService $store=new DatabaseService()) {}
    public static function slug(mixed $value): string {
        if (!is_string($value)||strlen($value)>36||!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$value)) throw new \InvalidArgumentException('Use a page slug of 1–36 lowercase letters, numbers and hyphens.');
        return $value;
    }
    public static function document(array $value,bool $publishing=false): array {
        if (!CmsSectionRegistry::exact($value,['title','description','sections'])||!is_array($value['sections'])) throw new \InvalidArgumentException('Invalid page document.');
        $title=CmsSectionRegistry::text($value['title'],120);
        if ($title==='') throw new \InvalidArgumentException('Enter a page title.');
        return ['title'=>$title,'description'=>CmsSectionRegistry::text($value['description'],300),
            'sections'=>CmsSectionRegistry::list($value['sections'],$publishing)];
    }
    public static function blank(string $slug): array {
        return ['title'=>ucwords(str_replace('-',' ',self::slug($slug))),'description'=>'','sections'=>[]];
    }
    public static function newRecord(string $slug,string $title): array {
        $slug=self::slug($slug);
        $draft=self::document(['title'=>$title,'description'=>'','sections'=>[]]);
        return ['id'=>$slug,'version'=>self::VERSION,'published'=>null,'draft'=>$draft,'previous'=>null,'can_restore'=>false];
    }
    public static function state(array $row): array {
        if (!CmsSectionRegistry::exact($row,['id','version','published','draft','previous','can_restore'])||
            ($row['version']??null)!==self::VERSION||!is_bool($row['can_restore']??null)) throw new \DomainException('Unsupported page document version.');
        self::slug($row['id']);
        foreach (['published','draft','previous'] as $key) {
            if ($row[$key]!==null) {
                if (!is_array($row[$key])) throw new \DomainException('Invalid saved page document.');
                $row[$key]=self::document($row[$key],$key!=='draft');
            }
        }
        if (!$row['can_restore']&&$row['previous']!==null) throw new \DomainException('Invalid page revision history.');
        return $row;
    }
    public static function editDocument(array $state): array {
        return $state['draft']??$state['published']??self::blank($state['id']);
    }
    /** Parse ordinary form input into exactly the same typed document contract. */
    public static function formDocument(array $input): array {
        $input['sections']??=[];
        if (!is_array($input['sections'])||!array_is_list($input['sections'])) throw new \InvalidArgumentException('Invalid section order.');
        foreach ($input['sections'] as &$section) {
            if (!is_array($section)||!in_array($section['visible']??null,['0','1'],true)) throw new \InvalidArgumentException('Invalid section visibility.');
            $section['visible']=$section['visible']==='1';
        }
        unset($section);
        return self::document($input);
    }
    public static function transition(array $row,string $action,?array $input=null): array {
        $row=self::state($row);
        if (in_array($action,['publish','unpublish','restore','discard'],true)) {
            switch ($action) {
                case 'publish':
                    if ($row['draft']===null) throw new \DomainException('Save a draft before publishing.');
                    $row['previous']=$row['published'];$row['can_restore']=true;
                    $row['published']=self::document($row['draft'],true);$row['draft']=null;break;
                case 'unpublish':
                    if ($row['published']===null) throw new \DomainException('Page is not published.');
                    $row['previous']=$row['published'];$row['can_restore']=true;
                    $row['published']=null;break;
                case 'restore':
                    if (!$row['can_restore']) throw new \DomainException('No prior publication.');
                    [$row['published'],$row['previous']]=[$row['previous'],$row['published']];$row['draft']=null;break;
                case 'discard':
                    $row['draft']=null;break;
            }
            return $row;
        }
        if ($input===null) throw new \InvalidArgumentException('Save a page document.');
        $doc=self::document($input);
        // New section IDs and type changes are server-authorized, not client-supplied.
        $current=self::editDocument($row);
        $before=array_column($current['sections'],'type','id');
        $after=array_column($doc['sections'],'type','id');
        if ($before!==$after) throw new \DomainException('Section structure changed. Reload the editor.');
        if ($action==='save') {
            $row['draft']=$doc;return $row;
        }
        [$op,$arg]=array_pad(explode(':',$action,2),2,'');
        if ($op==='add') {
            if (count($doc['sections'])>=CmsSectionRegistry::LIMIT) throw new \InvalidArgumentException('Section limit reached.');
            $doc['sections'][]=CmsSectionRegistry::fresh($arg);
        } else {
            $i=array_search($arg,array_column($doc['sections'],'id'),true);
            if ($i===false) throw new \InvalidArgumentException('Unknown section.');
            if ($op==='up'||$op==='down') {
                $j=$i+($op==='up'?-1:1);
                if (isset($doc['sections'][$j])) [$doc['sections'][$i],$doc['sections'][$j]]=[$doc['sections'][$j],$doc['sections'][$i]];
            } elseif ($op==='duplicate') {
                if (count($doc['sections'])>=CmsSectionRegistry::LIMIT) throw new \InvalidArgumentException('Section limit reached.');
                $copy=$doc['sections'][$i];$copy['id']=bin2hex(random_bytes(8));array_splice($doc['sections'],$i+1,0,[$copy]);
            } elseif ($op==='remove') {
                array_splice($doc['sections'],$i,1);
            } else throw new \InvalidArgumentException('Unknown section action.');
        }
        $row['draft']=self::document($doc);
        return $row;
    }
    public function ready(): bool {return $this->store->collectionExists('cms_pages');}

    /** Explicit owner setup on the serving installation; never run from public reads. */
    public function initialize(): void {
        if (getenv('BAPX_TEST_MODE')==='1') throw new \DomainException('Page setup requires a real database.');
        $this->store->ensureSchemaCollection('cms_pages');
        if (!$this->store->collectionExists('cms_pages')) throw new \RuntimeException('Page setup could not be confirmed.');
    }

    public function create(string $slug,string $title): array {
        return $this->store->createCmsPage($slug,$title);
    }
    public function editor(string $slug): array {
        $row=$this->store->find('cms_pages',self::slug($slug));
        if ($row===null) throw new \DomainException('Page not found.');
        return ['revision'=>ResourceService::revision($row),'state'=>self::state($row)];
    }
    public function save(string $slug,string $revision,string $action,?array $input): void {
        $this->store->saveCmsPage($slug,$revision,$action,$input);
    }
    /** Read only the published snapshot; private draft corruption never changes it. */
    public static function publishedDocument(array $row): ?array {
        self::slug($row['id']??null);
        if (($row['version']??null)!==self::VERSION||!array_key_exists('published',$row)) throw new \DomainException('Unsupported page publication.');
        if ($row['published']===null) return null;
        if (!is_array($row['published'])) throw new \DomainException('Invalid page publication.');
        return self::document($row['published'],true);
    }
    public function published(string $slug): ?array {
        $slug=self::slug($slug);
        if (!$this->ready()) return null;
        $row=$this->store->find('cms_pages',$slug);
        return $row===null?null:self::publishedDocument($row);
    }
    public function listing(bool $public=false): array {
        if (!$this->ready()) {
            if ($public) return [];
            throw new \DomainException('Initialize page storage before editing.');
        }
        $out=[];
        foreach ($this->store->read('cms_pages') as $row) {
            $slug=self::slug($row['id']??null);
            if ($public) {
                $doc=self::publishedDocument($row);
                if ($doc!==null) $out[]=['slug'=>$slug,'title'=>$doc['title']];
            } else {
                $s=self::state($row);$out[]=['slug'=>$slug,'title'=>self::editDocument($s)['title'],
                    'published'=>$s['published']!==null,'draft'=>$s['draft']!==null];
            }
        }
        usort($out,static fn(array $a,array $b):int=>strcmp($a['slug'],$b['slug']));
        return $out;
    }
}
