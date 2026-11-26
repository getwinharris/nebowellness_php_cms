<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('BAPX_TEST_MODE')!=='1')exit(2);
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REQUEST_URI']='/admin/pagespeed';
$_SERVER['HTTP_HOST']='psi-fixture.test';
require __DIR__.'/../../app/bootstrap.php';
$_SESSION['csrf_token']='fixture-token';
$_SESSION['user']=['role'=>'admin','email'=>'fixture@example.test'];
$origin='https://psi-fixture.test';$keyConfigured=false;
$reports=['/|mobile'=>['state'=>'ready','retrieved'=>time(),'data'=>['target'=>$origin.'/','strategy'=>'mobile','score'=>83,'analysis_at'=>time(),'lab'=>[['label'=>'LCP','value'=>2210,'unit'=>'ms']],'page_field'=>[],'origin_field'=>[['label'=>'INP','value'=>180,'unit'=>'ms','category'=>'FAST']]]],'/|desktop'=>['state'=>'quota','retrieved'=>null,'data'=>null]];
$pageTitle='PageSpeed Insights';$metaDescription='';$metaRobots='noindex';$seo=[];
$viewFile=app_path('views/admin/pagespeed.php');
require app_path('views/layouts/admin.php');
