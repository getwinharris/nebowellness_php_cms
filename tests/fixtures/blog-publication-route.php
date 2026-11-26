<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('BAPX_TEST_MODE')!=='1')exit(2);
$dir=$argv[1];$uri=$argv[2];
$_SERVER['REQUEST_METHOD']='GET';$_SERVER['REQUEST_URI']=$uri;$_SERVER['HTTP_HOST']='fixture.example.test';
$_SERVER['HTTP_ACCEPT']='text/markdown';
require dirname(__DIR__,2).'/app/bootstrap.php';
http_response_code(200);
register_shutdown_function(static function():void {echo "\nSTATUS:".http_response_code();});
$factory=static fn(string $class)=>$class===App\Controllers\BlogController::class
    ?new App\Controllers\BlogController(new App\Services\BlogService($dir)):new $class();
(new App\Router(require app_path('app/routes.php'),$factory))->dispatch('GET',$uri);
