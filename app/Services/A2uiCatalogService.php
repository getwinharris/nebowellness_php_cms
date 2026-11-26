<?php
declare(strict_types=1);
namespace App\Services;

/**
 * Native A2UI 1.0 literal snapshot subset. No renderer RPC or write actions.
 * Server ability metadata is separate from renderer functions and is not a grant.
 */
final class A2uiCatalogService
{
    public const VERSION = '1.0';
    public const ID = 'https://bapx.in/schemas/bapxcms/a2ui/1.0/catalog';
    private const READ_ABILITIES = [
        'published_site_identity', 'published_consultation_fees', 'published_practitioners',
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

    /** Local literal-only component envelope. */
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


    /** The snapshot is an atomic, bounded tree: no orphan, duplicate, cycle or shared child. */
    public static function validTree(array $components): bool
    {
        if (!array_is_list($components)||count($components)<1||count($components)>48) return false;
        $byId=[];
        foreach ($components as $component) {
            if (!is_array($component)||!self::validComponent($component)||isset($byId[$component['id']])) return false;
            $byId[$component['id']]=$component;
        }
        $seen=[];
        $walk=static function(string $id,int $depth) use (&$walk,&$seen,$byId):bool {
            if ($depth>6||isset($seen[$id])||!isset($byId[$id])) return false;
            $seen[$id]=true;
            foreach ($byId[$id]['children']??[] as $child) if (!$walk($child,$depth+1)) return false;
            return true;
        };
        return $walk('root',0)&&count($seen)===count($byId);
    }

    /**
     * Only fresh, successfully completed native model-call results enter here.
     * Never pass provider-authored components, arbitrary records or restored sessions.
     * This is a server-projected read-only snapshot, not a model-generated UI.
     */
    public function observedSurface(array $evidence,string $traceId): ?array
    {
        if (!preg_match('/^[a-f0-9]{16}$/D',$traceId)) return null;
        $allowed=array_column($this->serverAbilities(),null,'name');
        $sections=[];
        foreach (['published_site_identity','published_consultation_fees','published_programmes','search_articles','appointment_summary','therapy_enquiry_summary'] as $name) {
            $data=$evidence[$name]??null;
            if (!isset($allowed[$name])||!is_array($data)||!AbilitySchema::valid($data,$allowed[$name]['output_schema'])) continue;
            $items=[];$title='';
            switch ($name) {
                case 'published_site_identity':
                    $title=$data['name'].' · Published contact details';
                    foreach (['tagline'=>'','description'=>'','public_email'=>'Email: ','address'=>'Address: ','hours_weekday'=>'Hours: ','hours_weekend'=>'Hours: '] as $key=>$label) {
                        if ($data[$key]!=='') $items[]=['text'=>$label.$data[$key]];
                    }
                    foreach (['phones'=>'Phone: ','whatsapp'=>'WhatsApp: '] as $key=>$label) {
                        if ($data[$key]!==[]) $items[]=['text'=>$label.implode(', ',$data[$key])];
                    }
                    if ($items===[]) $items[]=['text'=>'No additional contact details are published.'];
                    $count=count($items);break;
                case 'published_consultation_fees':
                    $title='Published consultation fees (display only)';
                    foreach (array_slice($data['fees'],0,4) as $fee) $items[]=['label'=>$fee['title'],'value'=>$fee['display_price'].' · '.$fee['duration']];
                    $count=count($data['fees']);break;
                case 'published_programmes':
                    $title='Published programmes';
                    foreach (array_slice($data['programmes'],0,4) as $programme) {
                        $items[]=['text'=>$programme['title'].($programme['duration']!==''?' · '.$programme['duration']:'')];
                    }
                    $count=count($data['programmes']);break;
                case 'search_articles':
                    $title='Published article search';
                    foreach (array_slice($data['articles'],0,4) as $article) $items[]=['text'=>$article['title']];
                    $count=$data['found'];break;
                case 'appointment_summary':
                    $title='Historical appointments (not current bookings)';
                    $items[]=['label'=>'Total historical appointments','value'=>(string)$data['total']];
                    foreach ($data['by_status'] as $status=>$n) $items[]=['label'=>ucfirst($status),'value'=>(string)$n];
                    $count=count($items);break;
                case 'therapy_enquiry_summary':
                    $title='Therapy requests · last '.$data['days'].' days (not confirmed appointments)';
                    $items[]=['label'=>'Requests','value'=>(string)$data['request_count']];
                    foreach ($data['inbox_status'] as $status=>$n) $items[]=['label'=>ucfirst($status),'value'=>(string)$n];
                    $count=count($items);break;
            }
            if ($items===[]) $items[]=['text'=>'No matching published records.'];
            elseif ($count>count($items)) $items[]=['text'=>'Showing '.count($items).' of '.$count.' records.'];
            $sections[]=['title'=>$title,'items'=>$items];
            if (count($sections)>=2) break;
        }
        if ($sections===[]) return null;
        $components=[];$cards=[];
        foreach ($sections as $i=>$section) {
            $card='card_'.$i;$cards[]=$card;$children=[];
            foreach ($section['items'] as $j=>$item) {
                $id='item_'.$i.'_'.$j;$children[]=$id;
                $components[]=['id'=>$id,'component'=>isset($item['label'])?'Metric':'Text']+$item;
            }
            $components[]=['id'=>$card,'component'=>'Card','title'=>$section['title'],'children'=>$children];
        }
        array_unshift($components,['id'=>'root','component'=>'Stack','direction'=>'vertical','children'=>$cards]);
        if (!self::validTree($components)) return null;
        return ['version'=>self::VERSION,'createSurface'=>[
            'surfaceId'=>'records_'.$traceId,'catalogId'=>self::ID,'components'=>$components,
        ]];
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
