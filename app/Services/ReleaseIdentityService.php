<?php
declare(strict_types=1);
namespace App\Services;

/**
 * Bounded, read-only deployment evidence. HEAD is reported Git metadata, not
 * proof that files are clean. Fingerprint covers selected deployed source bytes,
 * not mutable customer data, PHP opcode caches or a complete Git tree.
 */
final class ReleaseIdentityService
{
    public const SCOPE = 'bapx-deployed-source-v1';
    private const MAX_ENTRIES = 8000;
    private const MAX_BYTES = 64 * 1024 * 1024;
    private const REQUIRED_FILES = ['index.php','.htaccess','storage/schema/collections.php'];
    private const OPTIONAL_CODE = ['config/agent-instructions.php','config/nebo-foundation-copy.php'];
    private const DIRECTORIES = [
        'app'=>['php'], 'api'=>['php'], 'views'=>['php'], 'integrations'=>['php'],
        'assets/css'=>['css'], 'assets/pwa'=>['js','json'],
    ];

    private string $root;
    public function __construct(string $root='') {
        $resolved=realpath($root!==''?$root:dirname(__DIR__,2));
        if ($resolved===false||!is_dir($resolved)) throw new \InvalidArgumentException('Invalid source root.');
        $this->root=rtrim($resolved,DIRECTORY_SEPARATOR);
    }

    /** @return array{0:int,1:array} */
    public function inspect(mixed $expectedRevision=null,mixed $expectedFingerprint=null): array {
        $data=['scope'=>self::SCOPE,'state'=>'unavailable','revision'=>null,'revision_match'=>null,
            'fingerprint'=>null,'fingerprint_match'=>null,'files'=>null];
        if (($expectedRevision!==null&&!self::hex($expectedRevision,40)) ||
            ($expectedFingerprint!==null&&!self::hex($expectedFingerprint,64))) {
            return [422,array_replace($data,['state'=>'invalid','error'=>'Expected SHA-1 revision or SHA-256 fingerprint is malformed.'])];
        }
        $before=$this->revision();
        try {
            [$fingerprint,$files]=$this->fingerprint();
        } catch (\Throwable) {
            return [503,$data+['error'=>'Deployed source fingerprint unavailable.']];
        }
        $after=$this->revision();
        if ($before!==$after) return [503,$data+['error'=>'Revision changed during inspection.']];
        $data['revision']=$after;
        $data['fingerprint']=$fingerprint;
        $data['files']=$files;
        if ($expectedRevision!==null && $after!==null) $data['revision_match']=hash_equals($after,strtolower($expectedRevision));
        if ($expectedFingerprint!==null) $data['fingerprint_match']=hash_equals($fingerprint,strtolower($expectedFingerprint));
        if ($data['revision_match']===false||$data['fingerprint_match']===false) {
            return [409,array_replace($data,['state'=>'mismatch'])];
        }
        if ($expectedRevision!==null&&$after===null) {
            return [503,$data+['error'=>'Git revision metadata is unavailable. Compare the source fingerprint separately.']];
        }
        $data['state']=$expectedRevision!==null||$expectedFingerprint!==null?'matched':'observed';
        return [200,$data];
    }

    private static function hex(mixed $value,int $length): bool {
        return is_string($value)&&strlen($value)===$length&&ctype_xdigit($value);
    }

    /** Never follow a .git file/worktree pointer, symlink or user-selected path. */
    private function gitText(string $relative,int $limit): ?string {
        $dir=$this->root.'/.git';
        if (is_link($dir)||!is_dir($dir)) return null;
        $candidate=$dir.'/'.$relative;
        $resolved=realpath($candidate);
        if ($resolved===false||$resolved!==$candidate||!is_file($resolved)) return null;
        $bytes=@file_get_contents($resolved,false,null,0,$limit+1);
        return is_string($bytes)&&strlen($bytes)<=$limit?$bytes:null;
    }

    private function revision(): ?string {
        clearstatcache(true);
        $head=$this->gitText('HEAD',1024);
        if ($head===null) return null;
        $head=trim($head);
        if (self::hex($head,40)) return strtolower($head);
        if (!preg_match('#^ref: (refs/heads/[A-Za-z0-9_./-]+)$#D',$head,$m)) return null;
        $ref=$m[1];
        foreach (explode('/',$ref) as $part) {
            if ($part===''||$part==='.'||$part==='..'||str_starts_with($part,'.')||str_contains($part,'..')||str_ends_with($part,'.lock')) return null;
        }
        $loose=$this->gitText($ref,1024);
        if ($loose!==null) return self::hex(trim($loose),40)?strtolower(trim($loose)):null;
        // An existing but unreadable/unsafe loose ref must not be replaced by stale packed metadata.
        if (file_exists($this->root.'/.git/'.$ref)||is_link($this->root.'/.git/'.$ref)) return null;
        $packed=$this->gitText('packed-refs',2*1024*1024);
        if ($packed===null) return null;
        foreach (explode("\n",$packed) as $line) {
            if (preg_match('/^([0-9a-fA-F]{40}) (.+)$/D',rtrim($line,"\r"),$m)&&$m[2]===$ref) return strtolower($m[1]);
        }
        return null;
    }

    /** @return array{0:string,1:int} */
    private function fingerprint(): array {
        $deadline=hrtime(true)+5_000_000_000;
        $entries=0;$files=[];
        foreach (self::REQUIRED_FILES as $file) $files[$file]=true;
        foreach (self::OPTIONAL_CODE as $file) {
            if (file_exists($this->root.'/'.$file)||is_link($this->root.'/'.$file)) $files[$file]=true;
        }
        foreach (self::DIRECTORIES as $dir=>$extensions) {
            $this->collect($dir,$extensions,$files,$entries,$deadline,0,true);
        }
        // Root asset files only. Never traverse uploads, images or fonts.
        $this->collect('assets',['js','css','html'],$files,$entries,$deadline,0,false);
        ksort($files,SORT_STRING);
        $hash=hash_init('sha256');hash_update($hash,self::SCOPE."\n");
        $total=0;
        foreach (array_keys($files) as $relative) {
            $this->guardBudget(++$entries,$deadline);
            $absolute=$this->root.'/'.$relative;
            if (realpath($absolute)!==$absolute||!is_file($absolute)||is_link($absolute)) throw new \RuntimeException('Source path unavailable.');
            $stream=@fopen($absolute,'rb');
            if ($stream===false) throw new \RuntimeException('Source read unavailable.');
            try {
                $before=fstat($stream);
                if ($before===false||($before['mode']&0170000)!==0100000||$before['size']<0||$before['size']>self::MAX_BYTES-$total) {
                    throw new \RuntimeException('Source size exceeds inspection budget.');
                }
                $fileHash=hash_init('sha256');$read=0;
                while (!feof($stream)) {
                    $this->guardBudget($entries,$deadline);
                    $bytes=fread($stream,65536);
                    if ($bytes===false||($bytes===''&&!feof($stream))) throw new \RuntimeException('Source read failed.');
                    $read+=strlen($bytes);$total+=strlen($bytes);
                    if ($total>self::MAX_BYTES) throw new \RuntimeException('Source size exceeds inspection budget.');
                    hash_update($fileHash,$bytes);
                }
                clearstatcache(true,$absolute);
                $after=@stat($absolute);
                if ($after===false||$read!==$before['size']||$after['size']!==$before['size']||$after['mtime']!==$before['mtime']||
                    $after['ino']!==$before['ino']||$after['dev']!==$before['dev']) throw new \RuntimeException('Source changed during inspection.');
                hash_update($hash,$relative."\0".hash_final($fileHash)."\n");
            } finally { fclose($stream); }
        }
        return [hash_final($hash),count($files)];
    }

    private function guardBudget(int $entries,int $deadline): void {
        if ($entries>self::MAX_ENTRIES||hrtime(true)>$deadline) throw new \RuntimeException('Inspection budget exceeded.');
    }

    private function collect(string $relative,array $extensions,array &$files,int &$entries,int $deadline,int $depth,bool $recursive): void {
        $this->guardBudget(++$entries,$deadline);
        if ($depth>16) throw new \RuntimeException('Source tree depth exceeds budget.');
        $absolute=$this->root.'/'.$relative;
        if (realpath($absolute)!==$absolute||!is_dir($absolute)||is_link($absolute)) throw new \RuntimeException('Source directory unavailable.');
        $dir=new \FilesystemIterator($absolute,\FilesystemIterator::SKIP_DOTS);
        foreach ($dir as $item) {
            $this->guardBudget(++$entries,$deadline);
            $name=$item->getFilename();
            if (str_starts_with($name,'.')) continue;
            $child=$relative.'/'.$name;
            if ($item->isDir()) {
                if ($recursive) $this->collect($child,$extensions,$files,$entries,$deadline,$depth+1,true);
                continue;
            }
            if (in_array(strtolower(pathinfo($name,PATHINFO_EXTENSION)),$extensions,true)) {
                if (!$item->isFile()||$item->isLink()) throw new \RuntimeException('Unsafe source entry.');
                $files[$child]=true;
            }
        }
    }
}
