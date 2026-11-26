<?php
declare(strict_types=1);
use App\Services\{AbilitySchema,AgentRunTrace,AgentToolRegistry,DatabaseService,NativeAbilityRegistry,SettingsService};
if(PHP_SAPI!=='cli'||!in_array(getenv('BAPX_MYSQL_HOST'),['localhost','127.0.0.1'],true)||
    !preg_match('/(?:_test|_fixture)$/D',(string)getenv('BAPX_MYSQL_DB'))||getenv('BAPX_TEST_MODE')==='1') {fwrite(STDERR,"Disposable local test database required.\n");exit(2);}
require __DIR__.'/../app/bootstrap.php';
$db=new DatabaseService(true);
$prefix='ownerqa-'.bin2hex(random_bytes(5));
$old=$db->find('settings',SettingsService::SETTINGS_ID);
$records=[
 ['users',['id'=>$prefix,'email'=>$prefix.'@example.test','name'=>'Private fixture owner count','role'=>'customer']],
 ['appointments',['id'=>$prefix,'status'=>'requested','customer_email'=>'private@example.test']],
 ['appointments',['id'=>$prefix.'-other','status'=>'legacy-unknown','customer_email'=>'private@example.test']],
 ['support_tickets',['id'=>$prefix,'status'=>'open','message'=>'order delivery problem with private fixture','customer_email'=>'private@example.test']],
 ['support_tickets',['id'=>$prefix.'-empty']],
 ['support_tickets',['id'=>$prefix.'-answered','status'=>'answered','message'=>'payment question','reply'=>'Payment reply']],
 ['campaign_pages',['id'=>$prefix,'slug'=>$prefix,'status'=>'published','title'=>'Public fixture campaign','summary'=>'Public summary','body'=>'Do not expose body in list']],
 ['campaign_pages',['id'=>$prefix.'-draft','slug'=>$prefix.'-draft','status'=>'draft','title'=>'Draft fixture campaign','summary'=>'Draft summary']],
 ['contact_submissions',['id'=>$prefix,'campaign_slug'=>$prefix,'status'=>'new','created_at'=>date('c',time()-60),'email'=>'private@example.test','form_data'=>['health'=>'private fixture']]],
 ['contact_submissions',['id'=>$prefix.'-old','campaign_slug'=>$prefix,'status'=>'closed','created_at'=>date('c',time()-100*86400)]],
 ['orders',['id'=>$prefix,'status'=>'confirmed','total'=>27.50,'customer_email'=>'private@example.test']],
 ['orders',['id'=>$prefix.'-pending','status'=>'pending','total'=>15.25]],
 ['orders',['id'=>$prefix.'-cancelled','status'=>'cancelled','total'=>999.00]],
];
$assert=static function(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);};
try {
    $db->upsert('settings',['id'=>SettingsService::SETTINGS_ID,'module_shop'=>'0','module_campaigns'=>'1']);
    $modules=['shop'=>false,'campaigns'=>true,'blog'=>false];
    $registry=new NativeAbilityRegistry($db,'admin',$modules);
    $baseline=$registry->invoke('owner_dashboard_summary',[]);
    $baselineAppointments=$registry->invoke('appointment_summary',[]);
    $baselineTickets=$registry->invoke('support_enquiries',['topic'=>'orders']);
    foreach ($records as [$collection,$record]) $db->upsert($collection,$record);
    $snapshot=static fn()=>array_map(static fn($r)=>$db->find($r[0],$r[1]['id']),$records);
    $before=$snapshot();
    $tools=new AgentToolRegistry($db,modules:$modules,scope:'admin');
    $support=new NativeAbilityRegistry($db,'support',$modules);
    $results=[];
    foreach (['owner_dashboard_summary'=>[],'appointment_summary'=>[],'support_enquiries'=>['topic'=>'orders'],
        'campaign_pages'=>['slug'=>$prefix.'-draft'],'therapy_enquiry_summary'=>['days'=>7,'slug'=>$prefix]] as $name=>$args) {
        $trace=new AgentRunTrace();
        $result=$tools->executeModelCall($name,$args,$trace,'fixture-'.$name);
        $assert(AbilitySchema::valid($result,$registry->inspect($name)['output_schema']),'Seeded owner ability output conforms: '.$name);
        $assert($trace->usedNativeTool([$name]),'Native trace records actual successful owner read: '.$name);
        $assert($support->inspect($name)===null&&$support->invoke($name,$args)['code']==='ability_not_available','Support cannot inspect or invoke owner capability');
        $assert(!str_contains(json_encode($result,JSON_THROW_ON_ERROR),'private@example.test')&&!str_contains(json_encode($result),'private fixture'),'No raw private data leaves owner projection');
        $results[$name]=$result;
    }
    $assert($results['owner_dashboard_summary']['registered_users']===$baseline['registered_users']+1&&!isset($results['owner_dashboard_summary']['commerce']),'Owner aggregate excludes disabled shop');
    $assert($results['appointment_summary']['by_status']['requested']===$baselineAppointments['by_status']['requested']+1&&
        $results['appointment_summary']['by_status']['other']===$baselineAppointments['by_status']['other']+1,'Historical appointment counts stable');
    $assert($results['support_enquiries']['total']===$baselineTickets['total']+2&&$results['support_enquiries']['matching']===$baselineTickets['matching']+1,'Real tickets counted; id-only rows excluded');
    $assert($results['campaign_pages']['found']===1&&$results['campaign_pages']['campaigns'][0]['public_url']===null,'Owner sees draft metadata, not a public URL');
    $assert($results['therapy_enquiry_summary']['request_count']===1,'Period and campaign filter excludes old record');
    $assert($snapshot()===$before,'All five owner reads leave durable fixture state unchanged');
    $db->upsert('settings',['id'=>SettingsService::SETTINGS_ID,'module_campaigns'=>'0','module_shop'=>'1']);
    $assert($registry->invoke('campaign_pages',[])['code']==='ability_not_available','Campaign module rechecked at invocation');
    $shop=new NativeAbilityRegistry($db,'admin',['shop'=>true,'campaigns'=>false]);
    $dashboard=$shop->invoke('owner_dashboard_summary',[]);
    $assert(AbilitySchema::valid($dashboard,$shop->inspect('owner_dashboard_summary')['output_schema']),'Commerce counts typed');
    $assert($dashboard['commerce']['confirmed_order_total_minor']>=2750&&$dashboard['commerce']['pending_order_total_minor']>=1525,'Stored order minor units reported separately');
    $assert(!isset($dashboard['commerce']['revenue']),'Never label stored order totals as verified revenue');
    $assert($snapshot()===$before,'No commerce mutation');
    echo "PASS MariaDB owner typed aggregate, role denial, stable status/period counts, draft metadata and read-only state\n";
} finally {
    foreach ($records as [$collection,$record]) $db->delete($collection,$record['id']);
    $db->delete('settings',SettingsService::SETTINGS_ID);
    if($old!==null)$db->upsert('settings',$old);
    SettingsService::flushCache();
}
