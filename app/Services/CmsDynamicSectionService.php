<?php
declare(strict_types=1);
namespace App\Services;

/** Read-only, request-local projection of existing native published abilities. */
final class CmsDynamicSectionService {
    public const SOURCES=[
        'programmes'=>['ability'=>'published_programmes','key'=>'programmes','module'=>'campaigns','source'=>'/campaigns'],
        'team'=>['ability'=>'published_practitioners','key'=>'practitioners','module'=>null,'source'=>'/#team'],
        'fee_table'=>['ability'=>'published_consultation_fees','key'=>'fees','module'=>null,'source'=>ConsultationFeeService::SOURCE],
    ];
    private array $results=[];
    public function __construct(private DatabaseService $store=new DatabaseService()) {}

    public static function supports(string $type): bool {return isset(self::SOURCES[$type]);}
    private static function state(string $state): array {
        return ['state'=>$state,'rows'=>[],'total'=>0,'source'=>'','scope'=>'','inclusions'=>[]];
    }

    /** Validate the actual native output again at the rendering projection boundary. */
    public static function project(string $type,array $result,int $limit,NativeAbilityRegistry $registry): array {
        if (!self::supports($type)||$limit<1||$limit>12) throw new \InvalidArgumentException('Invalid dynamic section.');
        $definition=self::SOURCES[$type];
        $schema=$registry->inspect($definition['ability']);
        if ($schema===null) return self::state('disabled');
        if (isset($result['error'])||!AbilitySchema::valid($result,$schema['output_schema'])) return self::state('unavailable');
        $rows=$result[$definition['key']];
        if ($rows===[]) return self::state('empty');
        return ['state'=>'ready','rows'=>array_slice($rows,0,$limit),'total'=>count($rows),
            'source'=>$definition['source'],'scope'=>$type==='fee_table'?$result['scope']:'',
            'inclusions'=>$type==='fee_table'?$result['inclusions']:[]];
    }

    public function resolve(string $type,int $limit): array {
        if (!self::supports($type)||$limit<1||$limit>12) throw new \InvalidArgumentException('Invalid dynamic section.');
        try {
            if (!isset($this->results[$type])) {
                $definition=self::SOURCES[$type];
                $modules=(new SettingsService($this->store))->strictModules();
                $registry=new NativeAbilityRegistry($this->store,'support',$modules);
                if ($registry->inspect($definition['ability'])===null) return self::state('disabled');
                $value=$registry->invoke($definition['ability'],[]);
                if (($value['code']??null)==='ability_not_available') return self::state('disabled');
                $this->results[$type]=[$registry,$value];
            }
            [$registry,$value]=$this->results[$type];
            return self::project($type,$value,$limit,$registry);
        } catch (\Throwable $error) {
            error_log('CMS dynamic section unavailable: '.get_class($error));
            return self::state('unavailable');
        }
    }
}
