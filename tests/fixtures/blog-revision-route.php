<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('BAPX_TEST_MODE')!=='1')exit(2);
$input=json_decode(stream_get_contents(STDIN),true,flags:JSON_THROW_ON_ERROR);
$_SERVER['REQUEST_METHOD']=$input['method']??'POST';
$_SERVER['REQUEST_URI']=$input['path'];
$_SERVER['HTTP_REFERER']='/admin/blog';
$_SERVER['HTTP_HOST']='blog-fixture.test';
session_save_path($argv[1]);session_id(bin2hex(random_bytes(16)));
require dirname(__DIR__,2).'/app/bootstrap.php';
if(($input['role']??'admin')!=='guest')$_SESSION['user']=['role'=>$input['role']??'admin','email'=>'fixture@example.test'];
$_SESSION['csrf_token']='fixture-token';
$_POST=($input['fields']??[])+['_csrf'=>'fixture-token'];
parse_str((string)parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY),$_GET);
http_response_code(200);
ob_start();
register_shutdown_function(static function():void{
    $body=ob_get_clean();
    echo json_encode(['status'=>http_response_code(),'flash'=>$_SESSION['flash']??null,'body'=>$body],JSON_THROW_ON_ERROR);
});
$factory=static fn(string $class)=>$class===App\Controllers\AdminController::class
    ?new App\Controllers\AdminController(new App\Services\BlogService($argv[2])):new $class();
(new App\Router(require app_path('app/routes.php'),$factory))->dispatch($_SERVER['REQUEST_METHOD'],$_SERVER['REQUEST_URI']);
