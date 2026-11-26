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
    $baseline=$r->invoke('sales_summary',['days'=>7]);
    foreach($records as [$collection,$row]) $db->upsert($collection,$row);
    $snapshot=static fn()=>array_map(static fn($entry)=>$db->find($entry[0],$entry[1]['id']),$records);
    $before=$snapshot();
    $tools=new AgentToolRegistry($db,modules:$modules,scope:'admin');
    $support=new NativeAbilityRegistry($db,'support',$modules);
    $results=[];
    foreach(['recent_orders'=>[],'catalog_categories'=>[],'find_order'=>['customer_email'=>$email],
        'product_status'=>['slug'=>$prefix],'sales_summary'=>['days'=>7],'coupon_status'=>['code'=>$code]] as $name=>$args) {
        $trace=new AgentRunTrace();
        $result=$tools->executeModelCall($name,$args,$trace,'fixture-'.$name);
        $assert(AbilitySchema::valid($result,$r->inspect($name)['output_schema']),'Real commerce output conforms: '.$name);
        $assert($trace->usedNativeTool([$name]),'Native trace succeeds: '.$name);
        $assert($support->inspect($name)===null&&$support->invoke($name,$args)['code']==='ability_not_available','Support denies '.$name);
        $encoded=json_encode($result,JSON_THROW_ON_ERROR);
        $assert(!str_contains($encoded,$email)&&!str_contains($encoded,'Private fixture'),'Customer identity/address never projected');
        $results[$name]=$result;
    }
    $assert($results['find_order']['found']===1&&$results['find_order']['orders'][0]['total_minor']===2750,'Exact filtered reference and amount');
    $assert($results['find_order']['orders'][0]['items'][0]['line_total_minor']===2750,'Exact line amount');
    $products=array_column($results['product_status']['products'],null,'slug');
    $assert($products[$prefix]['offer_price_minor']===1250&&$products[$prefix.'-hidden']['offer_price_minor']===null&&$products[$prefix.'-hidden']['hidden_from_shop'],'Active offer, expired offer and owner hidden flag');
    $sales=$results['sales_summary'];
    $assert($sales['orders']===$baseline['orders']+3&&$sales['confirmed_order_total_minor']===$baseline['confirmed_order_total_minor']+2750&&$sales['pending_order_total_minor']===$baseline['pending_order_total_minor']+1000,'Period excludes old order and separates cancelled/pending');
    $assert(in_array(['name'=>'Fixture product','units'=>2],$sales['best_sellers'],true),'Only confirmed in-period units counted');
    $assert($results['coupon_status']['usable_now']===null,'No invented cart eligibility');
    $assert($r->invoke('coupon_status',['code'=>$code,'cart_total_minor'=>500])['usable_now']===false,'Actual low cart refused');
    $assert($r->invoke('coupon_status',['code'=>$code,'cart_total_minor'=>2500])['usable_now']===null,'Missing per-customer usage unknown');
    $assert($r->invoke('coupon_status',['code'=>$code,'cart_total_minor'=>2500,'customer_email'=>$email])['usable_now']===false,'Used coupon refused');
    $assert($r->invoke('coupon_status',['code'=>$code,'cart_total_minor'=>2500,'customer_email'=>'unused@example.test'])['usable_now']===true,'Eligible supplied cart and customer');
    $assert($snapshot()===$before,'Commerce abilities leave business state unchanged');
    $db->upsert('settings',['id'=>SettingsService::SETTINGS_ID,'module_shop'=>'0']);
    foreach(array_keys($results) as $name) $assert($r->invoke($name,match($name){'find_order'=>['order_id'=>$prefix],'coupon_status'=>['code'=>$code],default=>[]})['code']==='ability_not_available','Stale shop capability denied');
    echo "PASS MariaDB typed commerce reads, exact money and periods, coupon context, actor/module denial and unchanged state\n";
} finally {
    foreach($records as [$collection,$row]) $db->delete($collection,$row['id']);
    $db->delete('settings',SettingsService::SETTINGS_ID);
    if($old!==null)$db->upsert('settings',$old);
    SettingsService::flushCache();
}
