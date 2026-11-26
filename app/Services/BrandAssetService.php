<?php
declare(strict_types=1);
namespace App\Services;

/** Validate brand assets and palette values before changing published settings. */
final class BrandAssetService
{
    public const PALETTE = [
        'palette_primary'=>'#4472C4','palette_secondary'=>'#70AD47',
        'palette_canvas'=>'#F7F9FA','palette_text'=>'#222222','palette_link'=>'#4472C4',
    ];

    private const TYPES = [
        'logo'=>[IMAGETYPE_PNG=>['png',['image/png']],IMAGETYPE_JPEG=>['jpg',['image/jpeg']]],
        'favicon'=>[IMAGETYPE_PNG=>['png',['image/png']],IMAGETYPE_ICO=>['ico',['image/x-icon','image/vnd.microsoft.icon']]],
    ];

    public function __construct(private ?string $directory=null) {}

    /** Read actual bytes, not the client-supplied extension, MIME or size. */
    public static function inspect(string $kind,string $file): string
    {
        if (!isset(self::TYPES[$kind])) throw new \InvalidArgumentException('Unknown brand asset.');
        $limit=$kind==='logo'?102400:51200;
        $dimension=$kind==='logo'?512:64;
        $size=is_file($file)?filesize($file):false;
        if ($size===false||$size<1||$size>$limit) throw new \InvalidArgumentException(ucfirst($kind).' has an invalid size.');
        $image=@getimagesize($file);
        $type=is_array($image)?($image[2]??0):0;
        $allowed=self::TYPES[$kind][$type]??null;
        $mime=function_exists('mime_content_type')?@mime_content_type($file):false;
        if ($mime===false&&class_exists(\finfo::class)) $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file);
        if ($mime===false) $mime=$image['mime']??false; // Server-detected image type, never client metadata.
        if (!$allowed||!in_array($mime,$allowed[1],true)) throw new \InvalidArgumentException(ucfirst($kind).' must be a supported raster image.');
        if (($image[0]??0)<1||($image[1]??0)<1||$image[0]>$dimension||$image[1]>$dimension) {
            throw new \InvalidArgumentException(ucfirst($kind).' exceeds the allowed dimensions.');
        }
        if (!function_exists('imagecreatefromstring')) throw new \RuntimeException('Image decoding is unavailable on this host. Existing artwork is unchanged.');
        if ($type===IMAGETYPE_ICO) self::validateIconDirectory($file,$size,$dimension);
        else {
            $bytes=file_get_contents($file);
            if ($bytes===false) throw new \InvalidArgumentException('Unreadable image.');
            self::decodeRaster($bytes,$type,$image[0],$image[1]);
        }
        return $allowed[0];
    }

    private static function decodeRaster(string $bytes,int $type,int $width,int $height): void {
        $info=@getimagesizefromstring($bytes);
        if (!$info||$info[2]!==$type||$info[0]!==$width||$info[1]!==$height) {
            throw new \InvalidArgumentException('Invalid image payload dimensions.');
        }
        // Some decoders return a partial image while reporting warnings. Reject those too.
        $warning=false;
        set_error_handler(static function() use (&$warning): bool {$warning=true;return true;});
        try {$decoded=imagecreatefromstring($bytes);} finally {restore_error_handler();}
        if (!$decoded||$warning) throw new \InvalidArgumentException('Image payload could not be decoded.');
    }

    /** Bound every icon directory entry, not only the first frame. */
    private static function validateIconDirectory(string $file,int $size,int $dimension): void
    {
        $bytes=file_get_contents($file);
        if ($bytes===false||strlen($bytes)<6) throw new \InvalidArgumentException('Invalid favicon directory.');
        $header=unpack('vreserved/vtype/vcount',substr($bytes,0,6));
        $count=$header['count']??0;
        $end=6+16*$count;
        if (($header['reserved']??1)!==0||($header['type']??0)!==1||$count<1||$count>32||$end>$size) {
            throw new \InvalidArgumentException('Invalid favicon directory.');
        }
        for ($i=0;$i<$count;$i++) {
            $entry=unpack('Cwidth/Cheight/Ccolors/Creserved/vplanes/vbits/Vlength/Voffset',substr($bytes,6+16*$i,16));
            if (!$entry||$entry['width']<1||$entry['height']<1||$entry['width']>$dimension||$entry['height']>$dimension||
                $entry['reserved']!==0||$entry['length']<1||$entry['offset']<$end||$entry['offset']>$size||$entry['length']>$size-$entry['offset']) {
                throw new \InvalidArgumentException('Invalid favicon frame.');
            }
            // Accept PNG-compressed ICO frames only; reject opaque DIB payloads.
            self::decodeRaster(substr($bytes,$entry['offset'],$entry['length']),IMAGETYPE_PNG,$entry['width'],$entry['height']);
        }
    }

    /** No-file is normal; any other upload failure must be surfaced. */
    public static function pending(string $kind,mixed $upload): ?array
    {
        if ($upload===null) return null;
        if (!is_array($upload)||!is_int($upload['error']??null)) throw new \InvalidArgumentException('Invalid upload.');
        if ($upload['error']===UPLOAD_ERR_NO_FILE) return null;
        if ($upload['error']!==UPLOAD_ERR_OK||!is_string($upload['tmp_name']??null)||!is_uploaded_file($upload['tmp_name'])) {
            throw new \InvalidArgumentException(ucfirst($kind).' upload did not complete.');
        }
        return ['tmp'=>$upload['tmp_name'],'extension'=>self::inspect($kind,$upload['tmp_name'])];
    }

    /** New immutable filename prevents stale browser cache and never overwrites old artwork. */
    public function store(string $kind,array $pending): string
    {
        $tmp=$pending['tmp']??null;
        if (!is_string($tmp)||!is_uploaded_file($tmp)) throw new \InvalidArgumentException('Invalid upload.');
        $ext=self::inspect($kind,$tmp);
        if (($pending['extension']??null)!==$ext) throw new \InvalidArgumentException('Upload changed.');
        $dir=$this->directory??app_path('assets/images/brand');
        if (!is_dir($dir)) @mkdir($dir,0775,true);
        if (is_link($dir)||!is_dir($dir)) throw new \RuntimeException('Brand asset storage unavailable.');
        $filename=$kind.'-'.bin2hex(random_bytes(16)).'.'.$ext;
        $dest=$dir.'/'.$filename;
        if (file_exists($dest)||!move_uploaded_file($tmp,$dest)) throw new \RuntimeException('Brand asset could not be stored.');
        return '/assets/images/brand/'.$filename;
    }

    public static function isManagedUrl(string $url,?string $kind=null): bool {
        $types=[
            'logo'=>'~^/assets/images/brand/logo-[0-9a-f]{32}\.(?:png|jpg)$~D',
            'favicon'=>'~^/assets/images/brand/favicon-[0-9a-f]{32}\.(?:png|ico)$~D',
        ];
        if ($kind!==null) return isset($types[$kind])&&preg_match($types[$kind],$url)===1;
        return preg_match($types['logo'],$url)===1||preg_match($types['favicon'],$url)===1;
    }

    /** Both uploads and palette input must pass validation before storing either file. */
    public static function prepare(array $settings,array $input,array $files): array {
        if (isset($input['reset_palette'])&&$input['reset_palette']!=='1') throw new \InvalidArgumentException('Invalid palette reset selection.');
        $patch=self::palettePatch($settings,$input);
        $pending=[];
        foreach (['logo','favicon'] as $kind) {
            if (isset($input[$kind.'_remove'])&&$input[$kind.'_remove']!=='1') throw new \InvalidArgumentException('Invalid restore-default selection.');
            $upload=self::pending($kind,$files[$kind.'_file']??null);
            if ($upload!==null&&!empty($input[$kind.'_remove'])) throw new \InvalidArgumentException('Choose either a new '.$kind.' or restore the default.');
            if ($upload!==null) $pending[$kind]=$upload;
            if (!empty($input[$kind.'_remove'])) $patch[$kind.'_url']='';
        }
        return ['patch'=>$patch,'pending'=>$pending];
    }

    /** Roll back only new files from this batch if storage fails before database writes. */
    public function storeBatch(array $pending): array {
        $created=[];
        try {
            foreach ($pending as $kind=>$upload) $created[$kind.'_url']=$this->store($kind,$upload);
            return $created;
        } catch (\Throwable $error) {
            foreach ($created as $url) {
                $file=($this->directory??app_path('assets/images/brand')).'/'.basename($url);
                if (self::isManagedUrl($url)&&is_file($file)&&!is_link($file)&&!@unlink($file)) {
                    error_log('Brand staging cleanup failed');
                }
            }
            throw $error;
        }
    }

    /** Record immutable asset provenance before publishing its settings reference. */
    public static function mediaRecord(string $url): array {
        if (!self::isManagedUrl($url)) throw new \InvalidArgumentException('Invalid brand asset URL.');
        $filename=basename($url);
        $kind=str_starts_with($filename,'logo-')?'logo':'favicon';
        $id=substr($filename,strlen($kind)+1,32);
        return ['id'=>$id,'filename'=>$filename,'original_name'=>ucfirst($kind),'url'=>$url,
            'description'=>'Brand '.$kind,'context'=>'brand','mime'=>match(pathinfo($filename,PATHINFO_EXTENSION)){
                'png'=>'image/png','jpg'=>'image/jpeg','ico'=>'image/x-icon',
            },'source'=>'upload','used_in'=>[['type'=>'settings','id'=>SettingsService::SETTINGS_ID,'name'=>'Appearance']],
            'created_at'=>date('c')];
    }

    public static function hex(mixed $value): ?string
    {
        if (!is_string($value)) return null;
        $v=strtoupper(trim($value));
        if (!preg_match('/^#(?:[0-9A-F]{3}|[0-9A-F]{6})$/D',$v)) return null;
        return strlen($v)===4?'#'.$v[1].$v[1].$v[2].$v[2].$v[3].$v[3]:$v;
    }

    /** Validate stored values again before emitting CSS, including legacy records. */
    public static function effectivePalette(array $settings): array
    {
        $result=[];
        foreach (self::PALETTE as $key=>$fallback) $result[$key]=self::hex($settings[$key]??null)??$fallback;
        return $result;
    }

    /** Patch only changed palette fields. Existing branding is unchanged until owner edits it. */
    public static function palettePatch(array $settings,array $input): array
    {
        $before=self::effectivePalette($settings);
        $after=$before;
        if (($input['reset_palette']??null)==='1') {
            // The old default link is slightly below 4.5:1 against the default canvas.
            $after=array_replace(self::PALETTE,['palette_link'=>'#416FC0']);
            return $after;
        }
        foreach (self::PALETTE as $key=>$_) {
            if (!array_key_exists($key,$input)) continue;
            $v=self::hex($input[$key]);
            if ($v===null) throw new \InvalidArgumentException('Invalid hex color for '.str_replace('_',' ',$key).'.');
            $after[$key]=$v;
        }
        $patch=array_diff_assoc($after,$before);
        foreach (['palette_text','palette_link'] as $key) {
            if ((isset($patch[$key])||isset($patch['palette_canvas']))&&self::contrast($after[$key],$after['palette_canvas'])<4.5) {
                throw new \InvalidArgumentException(str_replace('_',' ',$key).' contrast must be at least 4.5:1 against canvas.');
            }
        }
        return $patch;
    }

    public static function contrast(string $a,string $b): float
    {
        $luminance=static function(string $hex):float {
            $vals=[];
            foreach (str_split(substr($hex,1),2) as $c) {
                $s=hexdec($c)/255;$vals[]=$s<=0.04045?$s/12.92:(($s+0.055)/1.055)**2.4;
            }
            return 0.2126*$vals[0]+0.7152*$vals[1]+0.0722*$vals[2];
        };
        $x=$luminance($a);$y=$luminance($b);
        return (max($x,$y)+0.05)/(min($x,$y)+0.05);
    }
}
