<?php
declare(strict_types=1);
namespace App\Services;

/** Data-only section vocabulary. Never execute an authored template or script. */
final class CmsSectionRegistry {
    public const VERSION=1;
    public const LIMIT=20;
    public const TYPES=[
        'hero'=>['label'=>'Hero','fields'=>['heading'=>'Heading','body'=>'Supporting text']],
        'rich_text'=>['label'=>'Rich text','fields'=>['heading'=>'Heading','body'=>'Markdown content']],
        'image_text'=>['label'=>'Image and text','fields'=>['heading'=>'Heading','body'=>'Markdown content','image_url'=>'Image path','image_alt'=>'Image description']],
        'faq'=>['label'=>'FAQ','fields'=>['heading'=>'Question','body'=>'Answer (Markdown)']],
        'cta'=>['label'=>'Call to action','fields'=>['heading'=>'Heading','body'=>'Supporting text','link_label'=>'Button label','link_url'=>'Destination']],
    ];
    public const LIMITS=['heading'=>180,'body'=>6000,'image_url'=>300,'image_alt'=>240,'link_label'=>80,'link_url'=>400];

    public static function exact(array $value,array $keys): bool {
        return count($value)===count($keys)&&!array_diff(array_keys($value),$keys);
    }
    public static function text(mixed $value,int $max,bool $multiline=false): string {
        if (!is_string($value)||!mb_check_encoding($value,'UTF-8')) throw new \InvalidArgumentException('Enter valid text.');
        $value=trim($value);
        $control=$multiline?'/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f<>]/u':'/[\x00-\x1f\x7f<>]/u';
        if (mb_strlen($value)>$max||preg_match($control,$value)) throw new \InvalidArgumentException('Text exceeds its limit or contains unsupported markup.');
        return $value;
    }
    public static function imagePath(string $value): bool {
        return preg_match('~^/assets/images/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+\.(?:png|jpe?g|webp|gif)$~iD',$value)===1;
    }
    public static function linkPath(string $value): bool {
        if (preg_match('~^/(?:about|contact|support|terms|privacy|(?:campaigns|blog)(?:/[a-z0-9]+(?:-[a-z0-9]+)*)?|pages/[a-z0-9]+(?:-[a-z0-9]+)*)?(?:#[A-Za-z0-9_-]+)?$~D',$value)) return true;
        if (!filter_var($value,FILTER_VALIDATE_URL)||str_contains($value,'\\')) return false;
        $url=parse_url($value);
        return is_array($url)&&($url['scheme']??'')==='https'&&!empty($url['host'])&&!isset($url['user'])&&!isset($url['pass']);
    }
    public static function fresh(string $type): array {
        if (!isset(self::TYPES[$type])) throw new \InvalidArgumentException('Unknown section type.');
        return ['id'=>bin2hex(random_bytes(8)),'type'=>$type,'visible'=>true,
            'content'=>array_fill_keys(array_keys(self::TYPES[$type]['fields']),'')];
    }
    public static function validate(array $section,bool $publishing=false): array {
        if (!self::exact($section,['id','type','visible','content'])||
            !is_string($section['id'])||!preg_match('/^[a-f0-9]{16}$/D',$section['id'])||
            !is_string($section['type'])||!isset(self::TYPES[$section['type']])||
            !is_bool($section['visible'])||!is_array($section['content'])) {
            throw new \InvalidArgumentException('Invalid typed section.');
        }
        $keys=array_keys(self::TYPES[$section['type']]['fields']);
        if (!self::exact($section['content'],$keys)) throw new \InvalidArgumentException('Invalid section fields.');
        $content=[];
        foreach ($keys as $key) {
            $value=self::text($section['content'][$key],self::LIMITS[$key],$key==='body');
            if ($key==='image_url'&&$value!==''&&!self::imagePath($value)) throw new \InvalidArgumentException('Choose a safe local raster image.');
            if ($key==='link_url'&&$value!==''&&!self::linkPath($value)) throw new \InvalidArgumentException('Choose a public page path or HTTPS link.');
            if ($key==='body'&&preg_match('/^\s*#{1,2}\s+/m',$value)) throw new \InvalidArgumentException('Use the section heading field and level 3 or deeper Markdown headings.');
            $content[$key]=$value;
        }
        if ($publishing&&$section['visible']) {
            $required=match($section['type']) {
                'hero'=>['heading'],'rich_text'=>['body'],'image_text'=>['body','image_url','image_alt'],
                'faq'=>['heading','body'],'cta'=>['heading','link_label','link_url'],
            };
            foreach ($required as $field) if ($content[$field]==='') throw new \InvalidArgumentException('Complete visible '.$section['type'].' section fields before publishing.');
        }
        return ['id'=>$section['id'],'type'=>$section['type'],'visible'=>$section['visible'],'content'=>$content];
    }
    public static function list(array $sections,bool $publishing=false): array {
        if (!array_is_list($sections)||count($sections)>self::LIMIT) throw new \InvalidArgumentException('Use at most '.self::LIMIT.' ordered sections.');
        $seen=[];$out=[];$visible=0;
        foreach ($sections as $section) {
            if (!is_array($section)) throw new \InvalidArgumentException('Invalid section.');
            $item=self::validate($section,$publishing);
            if (isset($seen[$item['id']])) throw new \InvalidArgumentException('Section IDs must be unique.');
            $seen[$item['id']]=true;$out[]=$item;
            if ($item['visible']) $visible++;
        }
        if ($publishing&&$visible===0) throw new \InvalidArgumentException('Publish at least one complete visible section.');
        return $out;
    }
}
