<?php
declare(strict_types=1);
use App\Services\{AbilitySchema,AgentContextService,AgentToolRegistry,AgentRunTrace,DatabaseService,NativeAbilityRegistry,SettingsService};

if(PHP_SAPI!=='cli'||!in_array(getenv('BAPX_MYSQL_HOST'),['localhost','127.0.0.1'],true)||
    !preg_match('/(?:_test|_fixture)$/D',(string)getenv('BAPX_MYSQL_DB'))||getenv('BAPX_TEST_MODE')==='1'){fwrite(STDERR,"Disposable local test database required.\n");exit(2);}
require __DIR__.'/../app/bootstrap.php';

$db=new DatabaseService(true);
$old=$db->find('settings',SettingsService::SETTINGS_ID);
$key='sessionqa-'.bin2hex(random_bytes(6));
$own=$key.'@example.test';$other='other-'.$own;
$user=['role'=>'customer','email'=>$own];
$records=[
    ['orders',['id'=>$key.'-own','status'=>'confirmed','total'=>27.50,'customer_email'=>$own,'customer_name'=>'Private customer','shipping_address'=>['secret'=>'Private own address'],'invoice_number'=>'FIXTURE-1']],
    ['orders',['id'=>$key.'-other','status'=>'pending','total'=>99,'customer_email'=>$other,'shipping_address'=>['secret'=>'Private foreign address']]],
    ['products',['id'=>$key,'slug'=>$key,'name'=>'Public fixture','price'=>15,'status'=>'visible','description'=>'Public details','highlights'=>['Public highlight'],'description_points'=>['Public point'],'specifications'=>['Material'=>'Fixture material']]],
    ['products',['id'=>$key.'-hidden','slug'=>$key.'-hidden','name'=>'Hidden fixture product','price'=>30,'status'=>'hidden']],
];
$assert=static function(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);};
try {
    $db->upsert('settings',['id'=>SettingsService::SETTINGS_ID,'module_shop'=>'1','module_campaigns'=>'0','module_blog'=>'0','currency'=>'INR','clinic_phones'=>['+911234567890'],'private_secret'=>'Never project']);
    foreach($records as [$collection,$row]) $db->upsert($collection,$row);
    $snapshot=static fn()=>array_map(static fn($entry)=>$db->find($entry[0],$entry[1]['id']),$records);
    $before=$snapshot();
    $ctx=new AgentContextService($db);
    $modules=['shop'=>true,'campaigns'=>false,'blog'=>false];
    $cart=[['slug'=>$key,'name'=>'Public fixture','qty'=>2,'private_note'=>'Never project']];
    $make=static fn($actor,$question,$items=[])=>new NativeAbilityRegistry($db,'support',$modules,static fn()=>$ctx->supportContext($actor,$question,$items));
    $r=$make($user,true,$cart);
    $schema=$r->inspect('clinic_context')['output_schema'];
    $out=$r->invoke('clinic_context',[]);
    $assert(AbilitySchema::valid($out,$schema),'Real session output conforms');
    $assert($out['signed_in']&&!$out['account_access']&&$out['orders']===[],'Only authenticated requested account records');
    $assert(($out['site']['pages']['therapy requests']??null)==='/account/therapy','Customer service request navigation');
    $encoded=json_encode($out,JSON_THROW_ON_ERROR);
    foreach([$own,$other,$key.'-other','Private own address','Private foreign address','Never project','Hidden fixture product'] as $secret) $assert(!str_contains($encoded,$secret),'No private/unpublished source leak');
    $assert($out['cart']===[]&&$out['contact_channels']['phones']===['+911234567890'],'Trusted cart and canonical public contact');
    $assert($r->invoke('clinic_context',['email'=>$other])['code']==='ability_invalid_input','Model cannot select identity');
    $assert($make($user,false)->invoke('clinic_context',[])['orders']===[],'No account records for public question');
    $assert($make(null,true)->invoke('clinic_context',[])['orders']===[],'Guest sees no private records');
    $assert($make(['role'=>'admin','email'=>$own],true)->invoke('clinic_context',[])['orders']===[],'Owner support surface sees no customer account records');
    $assert((new NativeAbilityRegistry($db,'admin',$modules,static fn()=>$out))->inspect('clinic_context')===null,'Admin cannot discover session-only ability');
    $assert((new NativeAbilityRegistry($db,'support',$modules))->invoke('clinic_context',[])['code']==='ability_not_available','No trusted context loader means denied');
    $bad=$out;$bad['private_record']=$other;
    $assert((new NativeAbilityRegistry($db,'support',$modules,static fn()=>$bad))->invoke('clinic_context',[])['code']==='ability_invalid_output','Reject unexpected output fields');
    $bad=$out;$bad['account_access']=true;
    $assert((new NativeAbilityRegistry($db,'support',$modules,static fn()=>$bad))->invoke('clinic_context',[])['code']==='ability_invalid_output','Reject contradictory account scope');
    $db->upsert('settings',['id'=>SettingsService::SETTINGS_ID,'module_shop'=>'0','module_campaigns'=>'0','module_blog'=>'0']);
    $disabled=$r->invoke('clinic_context',[]);
    $assert(AbilitySchema::valid($disabled,$schema)&&!$disabled['shop_enabled']&&$disabled['orders']===[]&&$disabled['cart']===[]&&$disabled['site']['products']===[],'Actual disabled module rechecked');
    $stale=$out;$stale['shop_enabled']=true;
    $assert((new NativeAbilityRegistry($db,'support',$modules,static fn()=>$stale))->invoke('clinic_context',[])['code']==='ability_invalid_output','Reject stale enabled-shop context');
    $assert($snapshot()===$before,'Session context leaves business state unchanged');
    echo "PASS MariaDB native session context, owner isolation, strict schemas, module recheck and unchanged state\n";
} finally {
    foreach($records as [$collection,$row]) $db->delete($collection,$row['id']);
    $db->delete('settings',SettingsService::SETTINGS_ID);
    if($old!==null)$db->upsert('settings',$old);
    SettingsService::flushCache();
}
