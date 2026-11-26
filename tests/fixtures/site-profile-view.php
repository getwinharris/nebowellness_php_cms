<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli'||getenv('BAPX_TEST_MODE')!=='1') exit(2);
$input=json_decode(stream_get_contents(STDIN),true,flags:JSON_THROW_ON_ERROR);
$path=$argv[1]??'';
if (!in_array($path,['/contact','/admin/agent','/admin/site-profile','/manifest.json','/admin/manifest.json','/openapi.json','/'],true)) exit(2);
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REQUEST_URI']=$path;
$_SERVER['HTTP_HOST']='profile-fixture.test';
require __DIR__.'/../../app/bootstrap.php';
$_SESSION['csrf_token']='fixture-token';
if(str_starts_with($path,'/admin/')) $_SESSION['user']=['role'=>'admin','email'=>'fixture@example.test'];
// In-memory snapshot only: no production reads or writes.
$cache=new ReflectionProperty(App\Services\SettingsService::class,'cache');
$cache->setValue(null,$input['settings']);
if (!empty($input['unavailable'])) (new ReflectionProperty(App\Services\SettingsService::class,'cacheFailed'))->setValue(null,true);
if ($path==='/admin/site-profile') {
    $profile=(new App\Services\SiteProfileService())->published();
    $state=App\Services\SiteProfileService::state($input['settings']);
    $editor=['revision'=>App\Services\ResourceService::revision($input['settings']),'state'=>$state,'values'=>$state['draft']??$state['published']??App\Services\SiteProfileService::reference()];
    $profileError='';$publishedProfile=$profile;$pageTitle='Site profile';
    $viewFile=app_path('views/admin/site-profile.php');
    require app_path('views/layouts/admin.php');
} else {
    if($path==='/') $_SERVER['HTTP_ACCEPT']='text/markdown';
    (new App\Router(require app_path('app/routes.php')))->dispatch('GET',$path);
}
