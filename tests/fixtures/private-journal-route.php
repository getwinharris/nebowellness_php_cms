<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('BAPX_TEST_MODE')!=='1')exit(2);
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REQUEST_URI']=$argv[2];
$_SERVER['HTTP_HOST']='journal-fixture.test';
session_save_path($argv[1]);session_id(bin2hex(random_bytes(16)));
require dirname(__DIR__,2).'/app/bootstrap.php';
if($argv[3]!=='guest')$_SESSION['user']=['role'=>$argv[3],'email'=>'synthetic@example.test'];
$_SESSION['csrf_token']='fixture-token';
http_response_code(200);
register_shutdown_function(static function():void {echo "\nSTATUS:".http_response_code();});
(new App\Router(require app_path('app/routes.php')))->dispatch('GET',$_SERVER['REQUEST_URI']);
