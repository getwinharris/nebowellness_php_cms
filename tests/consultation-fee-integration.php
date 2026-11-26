<?php
declare(strict_types=1);
use App\Services\{AbilitySchema,AgentRunTrace,AgentToolRegistry,NativeAbilityRegistry,ConsultationFeeService,DatabaseService,ResourceService,SettingsService};
if(PHP_SAPI!=='cli'||!in_array(getenv('BAPX_MYSQL_HOST'),['localhost','127.0.0.1'],true)||!preg_match('/(?:_test|_fixture)$/D',(string)getenv('BAPX_MYSQL_DB'))||getenv('BAPX_TEST_MODE')==='1'){fwrite(STDERR,"Use a disposable local test database.\n");exit(2);}
require __DIR__.'/../app/bootstrap.php';
$db=new DatabaseService(true);
$old=$db->find('settings',SettingsService::SETTINGS_ID);
$assert=static function(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);};
try {
    $db->upsert('settings',['id'=>SettingsService::SETTINGS_ID,'module_shop'=>'0','consultation_fees'=>[],'consultation_inclusions'=>[],'qa_unrelated'=>'preserve']);
    $service=new ConsultationFeeService($db);
    $native=new NativeAbilityRegistry($db,'support',['shop'=>false,'blog'=>false,'campaigns'=>false]);
    $empty=$native->invoke('published_consultation_fees',[]);
    $assert(!isset($empty['error'])&&$empty['fees']===[],'Configured empty catalogue is a valid result');
    $rev=ResourceService::revision($service->settings());
    $input=['fee_action'=>'save','fee_id'=>'','title'=>'Fixture initial','description'=>'Remote appointment','amount'=>'27.50','currency'=>'USD','min_minutes'=>'30','max_minutes'=>'45','display_order'=>'10','status'=>'draft','inclusions'=>'Fixture inclusion'];
    $service->save($input,$rev);
    $assert($service->published()['fees']===[],'Draft hidden');
    $record=ConsultationFeeService::fromSettings($service->settings(),false)['fees'][0];
    $input['fee_id']=$record['id']; $input['status']='published';
    $stale=false;try{$service->save($input,$rev);}catch(DomainException){$stale=true;}
    $assert($stale,'Reject stale edit');
    $service->save($input,ResourceService::revision($service->settings()));
    $service->save(['fee_action'=>'inclusions','shared_inclusions'=>'Fixture shared'],ResourceService::revision($service->settings()));
    $catalogue=$service->published();
    $assert(count($catalogue['fees'])===1&&$catalogue['fees'][0]['amount_minor']===2750&&$catalogue['fees'][0]['display_price']==='USD 27.50','Exact published fee');
    $assert($catalogue['inclusions']===['Fixture shared'],'Shared inclusions');
    $assert(($service->settings()['qa_unrelated']??'')==='preserve','Other settings preserved');
    foreach(['support','admin'] as $scope) {
        $tools=new AgentToolRegistry($db,modules:['shop'=>false,'blog'=>false,'campaigns'=>false],scope:$scope);
        $trace=new AgentRunTrace();
        $before=$service->settings();
        $result=$tools->executeModelCall('published_consultation_fees',[],$trace,'fixture-fee-call');
        $metadata=(new NativeAbilityRegistry($db,$scope,[]))->inspect('published_consultation_fees');
        $assert(AbilitySchema::valid($result,$metadata['output_schema']),'Real seeded output conforms');
        $assert($before===$service->settings(),'Read capability leaves business settings unchanged');
        $assert(!isset($result['fees'][0]['status'],$result['fees'][0]['display_order']),'Private authoring fields withheld');
        $assert($tools->run('published_consultation_fees',['unexpected'=>true])['code']==='ability_invalid_input','Input rejects extra keys');

        $assert($result===$catalogue,'Same public tool output for '.$scope);
        $assert($trace->sources()===[ConsultationFeeService::SOURCE],'Verified source');
    }
    $input['status']='draft';
    $service->save($input,ResourceService::revision($service->settings()));
    $assert($service->published()['fees']===[],'Unpublish');
    $db->delete('settings',SettingsService::SETTINGS_ID);
    $assert($native->invoke('published_consultation_fees',[])['code']==='ability_unavailable','Absent canonical settings is unavailable');
    echo "PASS MariaDB fee typed output, read-only state, draft/publish, stale edits and missing catalogue\n";

}finally{
    $db->delete('settings',SettingsService::SETTINGS_ID);
    if($old!==null)$db->upsert('settings',$old);
    SettingsService::flushCache();
}
