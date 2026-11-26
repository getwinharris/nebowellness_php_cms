<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli'||getenv('BAPX_TEST_MODE')!=='1') exit(2);
$surface=$argv[1]??'';
if (!in_array($surface,['support','admin'],true)) exit(2);
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REQUEST_URI']=$surface==='admin'?'/admin/agent':'/support';
$_SERVER['HTTP_HOST']='nebo-fixture.test';
require __DIR__.'/../../app/bootstrap.php';
$_SESSION['csrf_token']='fixture-token';
if ($surface==='admin') $_SESSION['user']=['role'=>'admin','email'=>'fixture@example.test'];
(new App\Router(require app_path('app/routes.php')))->dispatch('GET',$_SERVER['REQUEST_URI']);
