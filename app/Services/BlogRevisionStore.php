<?php
declare(strict_types=1);
namespace App\Services;

/** Private immutable Markdown snapshots and one cooperative editorial write lock. */
final class BlogRevisionStore {
    public const MAX_BYTES=524288;
    public const MAX_REVISIONS=100;
    private string $root;

    public function __construct(string $postsDir) {
        $app=realpath(app_path());
        $posts=realpath($postsDir);
        if ($app===false||$posts===false||is_link($postsDir)) throw new \RuntimeException('Article storage unavailable.');
        $documentRoot=realpath((string)($_SERVER['DOCUMENT_ROOT']??''));
        $canonical=realpath(app_path('content/blog/posts'));
        // Fixture-specific roots must also be outside the served installation.
        $this->root=$posts===$canonical
            ?dirname($app).'/.bapxcms-blog-history-'.substr(hash('sha256',$app),0,24)
            :$posts.'/.private-revisions';
        foreach ([$app,$documentRoot] as $web) {
            if (is_string($web)&&$web!==''&&($this->root===$web||str_starts_with($this->root,rtrim($web,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR))) throw new \RuntimeException('Article revisions require private storage outside the web root.');
        }
    }
    private function directory(string $path,bool $create): bool {
        clearstatcache(true,$path);
        if(is_link($path))throw new \RuntimeException('Article history path unsafe.');
        if(!file_exists($path)){
            if(!$create)return false;
            if(!@mkdir($path,0700)&&!is_dir($path))throw new \RuntimeException('Article history unavailable.');
        }
        if(!is_dir($path)||!is_readable($path)||(fileperms($path)&0077)!==0)throw new \RuntimeException('Article history permissions unsafe.');
        return true;
    }
    public function locked(\Closure $operation,bool $create=true): mixed {
        if(!$this->directory($this->root,$create)) return $operation(false);
        $path=$this->root.'/.edit.lock';
        if(is_link($path))throw new \RuntimeException('Article lock unsafe.');
        $old=umask(0077);
        try{$handle=@fopen($path,'c+b');}finally{umask($old);}
        if($handle===false)throw new \RuntimeException('Article lock unavailable.');
        try{
            if(is_link($path)||!is_file($path)||(fileperms($path)&0077)!==0)throw new \RuntimeException('Article lock permissions unsafe.');
            $locked=false;
            for($i=0;$i<100;$i++){if(flock($handle,LOCK_EX|LOCK_NB)){$locked=true;break;}usleep(20000);}
            if(!$locked)throw new \RuntimeException('Article editing is busy. Try again.');
            return $operation(true);
        }finally{fclose($handle);}
    }
    private function folder(string $slug): string {
        if(!BlogService::validSlug($slug))throw new \InvalidArgumentException('Invalid article slug.');
        return $this->root.'/'.$slug;
    }
    public static function hashValid(string $hash): bool{return preg_match('/^[a-f0-9]{64}$/D',$hash)===1;}
    public static function read(string $path,bool $private=false): string {
        clearstatcache(true,$path);
        if(is_link($path)||!is_file($path)||!is_readable($path)||($private&&(fileperms($path)&0077)!==0)||filesize($path)>self::MAX_BYTES)throw new \RuntimeException('Article source unavailable or too large.');
        $bytes=@file_get_contents($path,false,null,0,self::MAX_BYTES+1);
        if(!is_string($bytes)||$bytes===''||strlen($bytes)>self::MAX_BYTES)throw new \RuntimeException('Article source unavailable or too large.');
        return $bytes;
    }
    public static function atomicWrite(string $path,string $bytes,int $mode): void {
        if($bytes===''||strlen($bytes)>self::MAX_BYTES||is_link($path))throw new \RuntimeException('Article write unavailable or too large.');
        $parent=dirname($path);$tmp=tempnam($parent,'.article-');
        if($tmp===false)throw new \RuntimeException('Article staging unavailable.');
        try{
            if(dirname($tmp)!==realpath($parent)||!chmod($tmp,$mode))throw new \RuntimeException('Article staging permissions unavailable.');
            $h=fopen($tmp,'wb');
            if($h===false)throw new \RuntimeException('Article staging unavailable.');
            try{if(fwrite($h,$bytes)!==strlen($bytes)||!fflush($h)||(function_exists('fsync')&&!fsync($h)))throw new \RuntimeException('Article save failed.');}finally{fclose($h);}
            if(!rename($tmp,$path))throw new \RuntimeException('Article commit failed.');
        }finally{if(is_file($tmp))@unlink($tmp);}
    }
    /** Internal: caller holds the edit lock. Snapshots are content addressed. */
    public function snapshot(string $slug,string $bytes): string {
        $dir=$this->folder($slug);$this->directory($dir,true);
        $hash=hash('sha256',$bytes);$path=$dir.'/'.$hash.'.md';
        if(file_exists($path)||is_link($path)){
            if(!hash_equals($hash,hash('sha256',self::read($path,true))))throw new \RuntimeException('Article revision checksum mismatch.');
            return $hash;
        }
        if(count($this->inventory($slug))>=self::MAX_REVISIONS)throw new \DomainException('Article revision limit reached. Review and back up history before further edits.');
        self::atomicWrite($path,$bytes,0600);return $hash;
    }
    /** Internal: only metadata, no historical content in the list. */
    public function inventory(string $slug): array {
        $dir=$this->folder($slug);
        if(!$this->directory($dir,false))return [];
        $rows=[];
        foreach(new \DirectoryIterator($dir) as $entry){
            if(!preg_match('/^([a-f0-9]{64})\.md$/D',$entry->getFilename(),$m))continue;
            if($entry->isLink()||!$entry->isFile()||($entry->getPerms()&0077)!==0||$entry->getSize()>self::MAX_BYTES)throw new \RuntimeException('Article revision inventory unsafe.');
            $rows[]=['revision'=>$m[1],'saved_at'=>date('c',$entry->getMTime()),'bytes'=>$entry->getSize()];
            if(count($rows)>self::MAX_REVISIONS)throw new \RuntimeException('Article revision inventory exceeds limit.');
        }
        usort($rows,static fn(array $a,array $b):int=>strcmp($b['saved_at'],$a['saved_at'])?:strcmp($a['revision'],$b['revision']));
        return $rows;
    }
    public function load(string $slug,string $hash): string {
        if(!self::hashValid($hash))throw new \InvalidArgumentException('Invalid article revision.');
        $dir=$this->folder($slug);
        if(!$this->directory($dir,false))throw new \DomainException('Article revision not found.');
        $path=$dir.'/'.$hash.'.md';
        if(!file_exists($path)&&!is_link($path))throw new \DomainException('Article revision not found.');
        $bytes=self::read($path,true);
        if(!hash_equals($hash,hash('sha256',$bytes)))throw new \RuntimeException('Article revision checksum mismatch.');
        return $bytes;
    }
}
