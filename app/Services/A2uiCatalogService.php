<?php
declare(strict_types=1);
namespace App\Services;

/**
 * Native catalog foundation for A2UI 1.0. No renderer or UI RPC is exposed yet.
 * Server ability metadata is separate from renderer functions and is not a grant.
 */
final class A2uiCatalogService
{
    public const VERSION = '1.0';
    public const ID = 'https://bapx.in/schemas/bapxcms/a2ui/1.0/catalog';
    private const READ_ABILITIES = [
        'published_consultation_fees', 'published_practitioners',
        'published_programmes', 'published_programme_detail',
        'search_clinic_knowledge', 'search_articles',
        'appointment_summary', 'campaign_pages', 'therapy_enquiry_summary',
    ];

    public function __construct(private NativeAbilityRegistry $registry) {}

    private static function text(int $max, int $min=1): array
    {
        return ['type'=>'string','minLength'=>$min,'maxLength'=>$max];
    }

    private static function componentId(): array
    {
        return self::text(64)+['pattern'=>'^[A-Za-z_][A-Za-z0-9_]*$'];
    }

    private static function components(): array
    {
        $definitions = [
            'Text' => ['text'=>self::text(4000)],
            'Stack' => [
                'direction'=>self::text(12)+['enum'=>['vertical','horizontal']],
                'children'=>['type'=>'array','items'=>self::componentId(),'minItems'=>1,'maxItems'=>24],
            ],
            'Metric' => ['label'=>self::text(120),'value'=>self::text(160)],
            'Card' => ['title'=>self::text(180),'children'=>['type'=>'array','items'=>self::componentId(),'minItems'=>1,'maxItems'=>12]],
        ];
        $schemas=[];
        foreach ($definitions as $name=>$properties) {
            $properties=['component'=>self::text(32)+['const'=>$name]]+$properties;
            $schemas[$name]=['type'=>'object','properties'=>$properties,'required'=>array_keys($properties)];
        }
        return $schemas;
    }

    public static function catalog(): array
    {
        $components=self::components();
        return [
            '$schema'=>'https://json-schema.org/draft/2020-12/schema',
            '$id'=>self::ID,'catalogId'=>self::ID,'protocolVersion'=>self::VERSION,
            'title'=>'bapXcms native read-only catalog',
            'description'=>'Literal text, bounded groups and metrics; no renderer functions or executable content.',
            'components'=>$components,'functions'=>(object)[],
            '$defs'=>[
                'anyComponent'=>['oneOf'=>array_map(static fn(string $name):array=>['$ref'=>'#/components/'.$name],array_keys($components))],
                'anyFunction'=>['not'=>(object)[]],
            ],
        ];
    }

    /** Local literal-only envelope; future surface lifecycle must validate the complete tree. */
    public static function validComponent(array $component): bool
    {
        $name=$component['component']??null;
        if (!is_string($name)) return false;
        $schema=self::components()[$name]??null;
        if ($schema===null) return false;
        $schema['properties']['id']=self::componentId();
        $schema['required'][]='id';
        $schema['additionalProperties']=false;
        if (!AbilitySchema::valid($component,$schema)) return false;
        $children=$component['children']??[];
        return count($children)===count(array_unique($children))&&!in_array($component['id'],$children,true);
    }

    /** Project trusted discovery only; never let a caller choose a role or execute a function here. */
    public function serverAbilities(): array
    {
        return array_values(array_filter($this->registry->discover(),static fn(array $ability):bool=>
            in_array($ability['name'],self::READ_ABILITIES,true)
            && ($ability['annotations']['readonly']??false)===true
            && ($ability['annotations']['destructive']??true)===false));
    }
}
