<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli'||getenv('BAPX_TEST_MODE')!=='1') exit(2);
$request=json_decode(stream_get_contents(STDIN),true,flags:JSON_THROW_ON_ERROR);
$_SERVER['REQUEST_METHOD']='POST';
$_SERVER['REQUEST_URI']='/admin/integrations/test-ai';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
session_save_path($argv[1]);
session_id(bin2hex(random_bytes(16)));
require __DIR__.'/../../app/bootstrap.php';
$_SESSION['csrf_token']='fixture-token';
if (($request['role']??'admin')!=='guest') $_SESSION['user']=['role'=>$request['role']??'admin','email'=>'owner@example.test'];
$_POST=$request['fields']+['_csrf'=>'fixture-token'];
register_shutdown_function(static function():void{
    echo json_encode(['status'=>http_response_code(),'result'=>$_SESSION['ai_test_result']??null],JSON_THROW_ON_ERROR);
});
(new App\Router(require app_path('app/routes.php')))->dispatch('POST',$_SERVER['REQUEST_URI']);
