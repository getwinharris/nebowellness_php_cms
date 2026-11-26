<?php
declare(strict_types=1);
use App\Services\{AbilitySchema,AgentRunTrace,AgentToolRegistry,DatabaseService,NativeAbilityRegistry,SettingsService};

if (PHP_SAPI!=='cli'||!in_array(getenv('BAPX_MYSQL_HOST'),['localhost','127.0.0.1'],true)||
    !preg_match('/(?:_test|_fixture)$/D',(string)getenv('BAPX_MYSQL_DB'))||getenv('BAPX_TEST_MODE')==='1') {
    fwrite(STDERR,"Use a disposable local test database.\n");exit(2);
}
require __DIR__.'/../app/bootstrap.php';
$db=new DatabaseService(true);
$old=$db->find('settings',SettingsService::SETTINGS_ID);
$prefix='qa-'.bin2hex(random_bytes(6));
$ids=[$prefix.'-public',$prefix.'-hidden',$prefix.'-draft',$prefix.'-duplicate'];
$assert=static function(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);};
try {
    $db->upsert('settings',['id'=>SettingsService::SETTINGS_ID,'module_campaigns'=>'1','module_shop'=>'0','module_blog'=>'1','consultation_fees'=>[],'consultation_inclusions'=>[]]);
    $public=['id'=>$ids[0],'slug'=>$ids[0],'name'=>'Fixture practitioner','status'=>'visible',
        'credentials'=>'Approved qualification','registration_number'=>'005441','speciality'=>'Fixture speciality',
        'description'=>'Public description','private_note'=>'Must never leave the source'];
    $db->upsert('consultants',$public);
    $db->upsert('consultants',array_replace($public,['id'=>$ids[1],'slug'=>$ids[1],'name'=>'Hidden fixture','status'=>'hidden']));
    $page=['id'=>$ids[0],'slug'=>$ids[0],'title'=>'Fixture programme','status'=>'published','summary'=>'Published summary',
        'body'=>'<p>Approved body.</p>','homepage_program'=>true,'program_area'=>'fixture','program_area_label'=>'Fixture',
        'program_duration'=>'8 weeks','program_order'=>1,'private_note'=>'Must never leave the source'];
    $db->upsert('campaign_pages',$page);
    $db->upsert('campaign_pages',array_replace($page,['id'=>$ids[2],'slug'=>$ids[2],'title'=>'Draft fixture','status'=>'draft']));
    $before=['settings'=>$db->find('settings',SettingsService::SETTINGS_ID),'practitioner'=>$db->find('consultants',$ids[0]),'page'=>$db->find('campaign_pages',$ids[0])];
    foreach (['support','admin'] as $scope) {
        $native=new NativeAbilityRegistry($db,$scope,['campaigns'=>true]);
        $legacy=new AgentToolRegistry($db,modules:['shop'=>false,'blog'=>false,'campaigns'=>true],scope:$scope);
        foreach (['published_practitioners'=>[],'published_programmes'=>[],'published_programme_detail'=>['slug'=>$ids[0]]] as $name=>$args) {
            $trace=new AgentRunTrace();
            $result=$legacy->executeModelCall($name,$args,$trace,'fixture-'.$name);
            $assert(AbilitySchema::valid($result,$native->inspect($name)['output_schema']),'Real seeded public result conforms: '.$name);
            $assert(!str_contains(json_encode($result,JSON_THROW_ON_ERROR),'Must never leave the source'),'No private authoring fields');
            $assert($trace->usedNativeTool([$name]),'Successful model-origin evidence for '.$name);
        }
        $practitioners=$legacy->run('published_practitioners',[])['practitioners'];
        $assert(in_array('Fixture practitioner',array_column($practitioners,'name'),true)&&!in_array('Hidden fixture',array_column($practitioners,'name'),true),'Visible practitioner only');
        $programmes=$legacy->run('published_programmes',[])['programmes'];
        $assert(in_array('/campaigns/'.$ids[0],array_column($programmes,'url'),true)&&!in_array('/campaigns/'.$ids[2],array_column($programmes,'url'),true),'Published opted-in programme only');
        $detail=$legacy->run('published_programme_detail',['slug'=>$ids[0]]);
        $assert($detail['body']==='Approved body.'&&$detail['duration']==='8 weeks','Canonical detail projection');
        $assert($legacy->run('published_programme_detail',['slug'=>$ids[2]])['code']==='ability_not_found','Draft inaccessible');
        $assert($legacy->run('published_programme_detail',['slug'=>'../bad'])['code']==='ability_invalid_input','Invalid slug rejected');
        $assert($before===['settings'=>$db->find('settings',SettingsService::SETTINGS_ID),'practitioner'=>$db->find('consultants',$ids[0]),'page'=>$db->find('campaign_pages',$ids[0])],'Read abilities leave durable state unchanged');
    }
    $native=new NativeAbilityRegistry($db,'support',['campaigns'=>true,'blog'=>true,'shop'=>false]);
    $search=$native->invoke('search_clinic_knowledge',['query'=>'Fixture programme']);
    $assert(AbilitySchema::valid($search,$native->inspect('search_clinic_knowledge')['output_schema']),'Published knowledge search has a typed result');
    $assert(in_array('/campaigns/'.$ids[0],array_column($search['matches'],'url'),true),'Find canonical published programme');
    $assert(!in_array('/campaigns/'.$ids[2],array_column($search['matches'],'url'),true),'Never cite a draft');
    $articles=$native->invoke('search_articles',['query'=>'Nebo consultation']);
    $assert(AbilitySchema::valid($articles,$native->inspect('search_articles')['output_schema']),'Actual published Markdown search conforms');
    $assert(in_array('/blog/your-first-nebo-consultation',array_column($articles['articles'],'path'),true),'Real article link');
    $db->upsert('settings',['id'=>SettingsService::SETTINGS_ID,'module_blog'=>'0']);
    $assert($native->invoke('search_articles',['query'=>'Nebo'])['code']==='ability_not_available','Stale blog discovery rechecks module');
    $db->upsert('settings',['id'=>SettingsService::SETTINGS_ID,'module_blog'=>'1']);
    $db->upsert('campaign_pages',array_replace($page,['id'=>$ids[3],'title'=>'Ambiguous fixture']));
    $assert($native->invoke('published_programme_detail',['slug'=>$ids[0]])['code']==='ability_not_found','Ambiguous published slug does not choose arbitrary record');
    $db->delete('campaign_pages',$ids[3]);
    $db->upsert('consultants',array_replace($public,['credentials'=>['invalid']]));
    $assert($native->invoke('published_practitioners',[])['code']==='ability_unavailable','Malformed source does not become literal Array or false success');
    $db->upsert('consultants',$public);
    $db->upsert('settings',['id'=>SettingsService::SETTINGS_ID,'module_campaigns'=>'0']);
    $assert($native->invoke('published_programmes',[])['code']==='ability_not_available','Stale discovered ability rechecks actual module');
    $assert($native->invoke('published_programme_detail',['slug'=>$ids[0]])['code']==='ability_not_available','Stale detail gate denied');
    $assert(!isset($native->invoke('published_practitioners',[])['error']),'Practitioner list independent of campaigns');
    echo "PASS MariaDB public typed ability output, publication/visibility, stale module, ambiguity, malformed source and unchanged state\n";
} finally {
    foreach ($ids as $id) {
        $db->delete('consultants',$id);$db->delete('campaign_pages',$id);
    }
    $db->delete('settings',SettingsService::SETTINGS_ID);
    if($old!==null)$db->upsert('settings',$old);
    SettingsService::flushCache();
}
