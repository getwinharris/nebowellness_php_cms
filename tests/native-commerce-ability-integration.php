<?php
declare(strict_types=1);
use App\Services\{AbilitySchema,AgentToolRegistry,AgentRunTrace,DatabaseService,NativeAbilityRegistry,SettingsService};
if(PHP_SAPI!=='cli'||!in_array(getenv('BAPX_MYSQL_HOST'),['localhost','127.0.0.1'],true)||
    !preg_match('/(?:_test|_fixture)$/D',(string)getenv('BAPX_MYSQL_DB'))||getenv('BAPX_TEST_MODE')==='1'){fwrite(STDERR,"Disposable local test database required.\n");exit(2);}
require __DIR__.'/../app/bootstrap.php';
$db=new DatabaseService(true);
$old=$db->find('settings',SettingsService::SETTINGS_ID);
$prefix='shopqa-'.bin2hex(random_bytes(6));$email=$prefix.'@example.test';$code=strtoupper($prefix);
$recent=date('c',time()-3600);$older=date('c',time()-50*86400);
$item=['slug'=>$prefix,'name'=>'Fixture product','qty'=>2,'line_total'=>27.50];
$records=[
 ['orders',['id'=>$prefix.'-confirmed','status'=>'confirmed','total'=>27.50,'created_at'=>$recent,'items'=>[$item],'customer_email'=>$email,'customer_name'=>'Private fixture customer','shipping_address'=>'Private fixture address','coupon_code'=>$code,'tracking_id'=>'fixture-tracking']],
 ['orders',['id'=>$prefix.'-pending','status'=>'pending','total'=>10.00,'created_at'=>$recent,'items'=>[array_replace($item,['qty'=>50])],'customer_email'=>'other@example.test']],
 ['orders',['id'=>$prefix.'-old','status'=>'confirmed','total'=>99.00,'created_at'=>$older,'items'=>[array_replace($item,['qty'=>100])]]],
 ['orders',['id'=>$prefix.'-cancelled','status'=>'cancelled','total'=>999.00,'created_at'=>$recent]],
 ['products',['id'=>$prefix,'slug'=>$prefix,'name'=>'Fixture product','price'=>15.00,'offer_price'=>12.50,'offer_starts_at'=>date('c',time()-3600),'offer_ends_at'=>date('c',time()+3600),'stock_status'=>'in_stock','status'=>'visible']],
 ['products',['id'=>$prefix.'-hidden','slug'=>$prefix.'-hidden','name'=>'Fixture hidden product','price'=>30.00,'offer_price'=>5.00,'offer_ends_at'=>$older,'stock_status'=>'out_of_stock','status'=>'hidden']],
 ['categories',['id'=>$prefix,'slug'=>$prefix,'name'=>'Fixture category']],
 ['coupons',['id'=>$prefix,'code'=>$code,'status'=>'active','discount_type'=>'percentage','discount_value'=>10,'min_spend'=>10,'max_spend'=>100,'usage_limit'=>20,'usage_limit_per_customer'=>1]],
];
$assert=static function(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);};
try {
    $db->upsert('settings',['id'=>SettingsService::SETTINGS_ID,'module_shop'=>'1','module_campaigns'=>'0','module_blog'=>'0']);
    $modules=['shop'=>true,'campaigns'=>false,'blog'=>false];
    $r=new NativeAbilityRegistry($db,'admin',$modules);
    foreach($records as [$collection,$row]) $db->upsert($collection,$row);
    $snapshot=static fn()=>array_map(static fn($entry)=>$db->find($entry[0],$entry[1]['id']),$records);
    $before=$snapshot();
    $assert(!in_array('shop',array_keys(SettingsService::MODULES),true),'No re-enable switch');
    $assert(!(new SettingsService($db))->strictModules()['shop'],'Stored enabled shop flag cannot restore retirement');
    foreach(['admin','support'] as $scope) {
        $registry=new NativeAbilityRegistry($db,$scope,$modules);
        $tools=new AgentToolRegistry($db,modules:$modules,scope:$scope);
        foreach(['recent_orders','catalog_categories','find_order','product_status','sales_summary','coupon_status'] as $name) {
            $assert(!$registry->registered($name)&&$registry->inspect($name)===null,'No definition: '.$name);
            $assert($registry->invoke($name,[])['code']==='ability_not_available'&&!$tools->has($name),'No invocation: '.$name);
        }
    }
    $assert($snapshot()===$before,'Commerce abilities leave business state unchanged');
    echo "PASS MariaDB retired retail tools cannot be enabled and historical state remains unchanged\n";
} finally {
    foreach($records as [$collection,$row]) $db->delete($collection,$row['id']);
    $db->delete('settings',SettingsService::SETTINGS_ID);
    if($old!==null)$db->upsert('settings',$old);
    SettingsService::flushCache();
}
