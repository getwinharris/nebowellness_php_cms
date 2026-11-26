<?php
declare(strict_types=1);
namespace App\Services;

/** Bounded, fail-closed native subset. Not a general JSON Schema engine. */
final class AbilitySchema
{
    public static function valid(mixed $value,array $schema,int $depth=0): bool {
        if ($depth>12) return false;
        $type=$schema['type']??null;
        $allowed=match($type) {
            'object'=>['type','properties','required','additionalProperties'],
            'array'=>['type','items','maxItems','minItems'],
            'string'=>['type','minLength','maxLength','pattern','enum','const'],
            'integer'=>['type','minimum','maximum','enum','const'],
            'boolean'=>['type','const'],
            default=>[],
        };
        if (!$allowed||array_diff(array_keys($schema),$allowed)) return false;
        if (array_key_exists('const',$schema)&&$value!==$schema['const']) return false;
        if (isset($schema['enum'])&&(!is_array($schema['enum'])||!in_array($value,$schema['enum'],true))) return false;
        switch($type) {
            case 'object':
                if (!is_array($value)||($value!==[]&&array_is_list($value))||($schema['additionalProperties']??null)!==false) return false;
                $properties=$schema['properties']??null;$required=$schema['required']??null;
                if ($properties instanceof \stdClass) $properties=(array)$properties;
                if (!is_array($properties)||!is_array($required)||!array_is_list($required)) return false;
                foreach ($required as $key) if (!is_string($key)||!array_key_exists($key,$properties)||!array_key_exists($key,$value)) return false;
                foreach ($value as $key=>$field) if (!is_string($key)||!isset($properties[$key])||!is_array($properties[$key])||!self::valid($field,$properties[$key],$depth+1)) return false;
                return true;
            case 'array':
                if (!is_array($value)||!array_is_list($value)||!is_int($schema['maxItems']??null)||$schema['maxItems']<0||$schema['maxItems']>500||count($value)>$schema['maxItems']||count($value)<($schema['minItems']??0)||!is_array($schema['items']??null)) return false;
                foreach ($value as $item) if (!self::valid($item,$schema['items'],$depth+1)) return false;
                return true;
            case 'string':
                if (!is_string($value)||!mb_check_encoding($value,'UTF-8')||!is_int($schema['maxLength']??null)||mb_strlen($value)>$schema['maxLength']||mb_strlen($value)<($schema['minLength']??0)) return false;
                return !isset($schema['pattern'])||preg_match('~'.str_replace('~','\~',$schema['pattern']).'~uD',$value)===1;
            case 'integer':
                return is_int($value)&&$value>=($schema['minimum']??PHP_INT_MIN)&&$value<=($schema['maximum']??PHP_INT_MAX);
            case 'boolean':
                return is_bool($value);
        }
        return false;
    }
}
