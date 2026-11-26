<?php
declare(strict_types=1);
// Explicit opt-in, isolated local database, no production entrypoint.
if(PHP_SAPI!=='cli-server'||getenv('BAPX_BRAND_ROUTE_FIXTURE')!=='1'
    ||getenv('BAPX_TEST_MODE')==='1'||!in_array(getenv('BAPX_MYSQL_HOST'),['localhost','127.0.0.1'],true)
    ||!preg_match('/(?:_test|_fixture)$/D',(string)getenv('BAPX_MYSQL_DB'))
    ||($_SERVER['REMOTE_ADDR']??'')!=='127.0.0.1'){
    http_response_code(404);exit;
}
session_save_path((string)getenv('BAPX_BRAND_SESSION_DIR'));
session_id('brand-appearance-fixture');
require __DIR__.'/../../app/bootstrap.php';
$_SESSION['csrf_token']='brand-fixture-csrf';
$_SESSION['user']=match($_SERVER['HTTP_X_FIXTURE_ROLE']??'admin'){
    'admin'=>['role'=>'admin','name'=>'Brand fixture','email'=>'brand-fixture@example.test'],
    'customer'=>['role'=>'customer','email'=>'customer@example.test'],
    default=>null,
};
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if($path==='/__fixture/flash'){
    header('Content-Type: application/json');
    echo json_encode($_SESSION['flash']??null,JSON_THROW_ON_ERROR);
    unset($_SESSION['flash']);
    exit;
}
if(!in_array($path,['/admin/appearance','/admin/appearance/save'],true)){http_response_code(404);exit;}
(new App\Router(require __DIR__.'/../../app/routes.php'))->dispatch($_SERVER['REQUEST_METHOD'],$_SERVER['REQUEST_URI']);
