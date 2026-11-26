<?php
declare(strict_types=1);
namespace App\Services;

/**
 * Native read-ability catalogue. The scope is supplied by trusted existing
 * agent entry points, never by a model argument. No external transport yet.
 */
final class NativeAbilityRegistry
{
    public function __construct(private DatabaseService $store,private string $scope,private array $modules) {
        if (!in_array($scope,['support','admin'],true)) throw new \InvalidArgumentException('Unknown native ability scope.');
    }

    private static function object(array $properties): array {
        return ['type'=>'object','properties'=>$properties===[]?(object)[]:$properties,
            'required'=>array_keys($properties),'additionalProperties'=>false];
    }

    private static function string(int $max,int $min=0): array {
        return ['type'=>'string','minLength'=>$min,'maxLength'=>$max];
    }

    private static function listOf(array $item,int $max): array {
        return ['type'=>'array','items'=>$item,'maxItems'=>$max];
    }

    private static function definition(string $description,array $output,array $input=[],?string $module=null): array {
        return ['version'=>1,'category'=>'published-clinic','description'=>$description,
            'input_schema'=>self::object($input),'output_schema'=>$output,
            'annotations'=>['readonly'=>true,'destructive'=>false,'idempotent'=>true],
            'scopes'=>['support','admin'],'module'=>$module];
    }

    private static function definitions(): array {
        $fee=self::object([
            'id'=>self::string(40,1)+['pattern'=>'^[a-z0-9][a-z0-9-]{0,39}$'],
            'title'=>self::string(100,1),'description'=>self::string(500),
            'amount_minor'=>['type'=>'integer','minimum'=>0,'maximum'=>999999999],
            'currency'=>self::string(3,3)+['enum'=>array_keys(ConsultationFeeService::CURRENCIES)],
            'min_minutes'=>['type'=>'integer','minimum'=>1,'maximum'=>1440],
            'max_minutes'=>['type'=>'integer','minimum'=>1,'maximum'=>1440],
            'inclusions'=>self::listOf(self::string(200,1),20),
            'display_price'=>self::string(40,1),'duration'=>self::string(40,1),
        ]);
        $practitioner=self::object([
            'name'=>self::string(200,1),'credentials'=>self::string(1000),
            'speciality'=>self::string(300),'registration_number'=>self::string(80),
            'url'=>self::string(50,1)+['const'=>'/#team'],
        ]);
        $programme=[
            'title'=>self::string(200,1),'summary'=>self::string(6000),
            'duration'=>self::string(80),
            'url'=>self::string(131,1)+['pattern'=>'^/campaigns/[a-z0-9]+(?:-[a-z0-9]+)*$'],
        ];
        return [
            'published_consultation_fees'=>self::definition(
                'Read current published online consultation fees, currency, duration and included services. Display prices only, not programme package prices, payment offers or confirmed bookings.',
                self::object([
                    'fees'=>self::listOf($fee,ConsultationFeeService::LIMIT),
                    'inclusions'=>self::listOf(self::string(200,1),20),
                    'source'=>self::string(100,1)+['const'=>ConsultationFeeService::SOURCE],
                    'scope'=>self::string(200,1)+['const'=>ConsultationFeeService::PUBLIC_SCOPE],
                ])
            ),
            'published_practitioners'=>self::definition(
                'Read visible clinic practitioner profiles, professional credentials and published registration numbers.',
                self::object(['practitioners'=>self::listOf($practitioner,40)])
            ),
            'published_programmes'=>self::definition(
                'Read published homepage programmes and their durations and detail links.',
                self::object(['programmes'=>self::listOf(self::object($programme),30)]),[],'campaigns'
            ),
            'published_programme_detail'=>self::definition(
                'Retrieve one published programme detail by its exact slug.',
                self::object($programme+['body'=>self::string(12000)]),
                ['slug'=>self::string(120,1)+['pattern'=>'^[a-z0-9]+(?:-[a-z0-9]+)*$']],'campaigns'
            ),
        ];
    }

    public function registered(string $name): bool { return isset(self::definitions()[$name]); }

    private function allowed(array $definition): bool {
        return in_array($this->scope,$definition['scopes'],true)
            && ($definition['module']===null||!empty($this->modules[$definition['module']]));
    }

    public function inspect(string $name): ?array {
        $definition=self::definitions()[$name]??null;
        return $definition!==null&&$this->allowed($definition)?['name'=>$name]+$definition:null;
    }

    public function discover(): array {
        $result=[];
        foreach (array_keys(self::definitions()) as $name) if (($definition=$this->inspect($name))!==null) $result[]=$definition;
        return $result;
    }

    /** Preserve the existing model function name and provider projection. */
    public function declarations(): array {
        return array_map(static function(array $definition):array {
            $input=$definition['input_schema'];
            // The transport projection keeps the supported provider schema vocabulary.
            // The full native constraints are enforced again before domain invocation.
            $input['properties']=array_map(static fn(array $field):array=>array_intersect_key($field,['type'=>true,'description'=>true]),(array)$input['properties']);
            return ['name'=>$definition['name'],'description'=>$definition['description'],'parameters'=>$input];
        },$this->discover());
    }

    private static function error(string $code,string $message): array {
        return ['error'=>$message,'code'=>$code];
    }

    public function invoke(string $name,array $args): array {
        $definition=$this->inspect($name);
        if ($definition===null) return self::error('ability_not_available','Unknown or unavailable ability.');
        if (!AbilitySchema::valid($args,$definition['input_schema'])) return self::error('ability_invalid_input','Invalid ability input.');
        try {
            // Recheck the native module at execution; discovery alone is not authority.
            if ($definition['module']!==null&&!((new SettingsService($this->store))->strictModules()[$definition['module']]??false)) {
                return self::error('ability_not_available','Unknown or unavailable ability.');
            }
            $result=match($name) {
                'published_consultation_fees'=>(new ConsultationFeeService($this->store))->published(),
                'published_practitioners'=>['practitioners'=>(new ClinicKnowledgeService($this->store))->practitioners()],
                'published_programmes'=>['programmes'=>(new ClinicKnowledgeService($this->store))->programmes()],
                'published_programme_detail'=>(new ClinicKnowledgeService($this->store))->programmeDetail($args['slug']),
                default=>throw new \LogicException('Unmapped native ability.'),
            };
            if ($result===null) return self::error('ability_not_found','Published programme not found.');
            $encoded=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            if (strlen($encoded)>48000||!AbilitySchema::valid($result,$definition['output_schema'])) {
                return self::error('ability_invalid_output','The lookup did not return a valid bounded result.');
            }
            return $result;
        } catch (\Throwable $error) {
            error_log('Native ability '.$name.' unavailable: '.get_class($error));
            return self::error('ability_unavailable','The lookup is unavailable right now.');
        }
    }
}
