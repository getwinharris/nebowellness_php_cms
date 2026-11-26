<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('BAPX_TEST_MODE')!=='1')exit(2);
$input=json_decode(stream_get_contents(STDIN),true,flags:JSON_THROW_ON_ERROR);
$_SERVER['REQUEST_METHOD']='POST';
$_SERVER['REQUEST_URI']='/admin/pagespeed/run';
$_SERVER['HTTP_REFERER']='/admin/pagespeed';
session_save_path($argv[1]);session_id(bin2hex(random_bytes(16)));
require __DIR__.'/../../app/bootstrap.php';
$_SESSION['csrf_token']='fixture-token';
if(($input['role']??'admin')!=='guest') $_SESSION['user']=['role'=>$input['role']??'admin','email'=>'owner@example.test'];
// Only invalid requests or unauthorized actors are accepted by this fixture.
// No actual Google network request or business-record mutation.
$_POST=$input['fields']+['_csrf'=>'fixture-token'];
if (($input['role']??'admin')==='admin'&&$_POST['_csrf']==='fixture-token'
    &&in_array($_POST['path']??null,['/','/about'],true)&&in_array($_POST['strategy']??null,['mobile','desktop'],true)) exit(3);
register_shutdown_function(static function():void{
    echo json_encode(['status'=>http_response_code(),'flash'=>$_SESSION['flash']??null,'reports'=>$_SESSION['pagespeed_reports']??null],JSON_THROW_ON_ERROR);
});
(new App\Router(require app_path('app/routes.php')))->dispatch('POST',$_SERVER['REQUEST_URI']);
