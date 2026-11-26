<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli'||getenv('BAPX_TEST_MODE')!=='1') exit(2);
$path=$argv[1]??'';
if(!in_array($path,['/admin/pages','/pages/fixture'],true))exit(2);
$_SERVER['REQUEST_METHOD']='GET';$_SERVER['REQUEST_URI']=$path;$_SERVER['HTTP_HOST']='cms-fixture.test';
require __DIR__.'/../../app/bootstrap.php';
$_SESSION['csrf_token']='fixture-token';
$service=App\Services\CmsPageService::class;
$r=$service::newRecord('fixture','Reusable page fixture');
$r=$service::transition($r,'add:hero',$r['draft']);
$d=$r['draft'];$d['sections'][0]['content']=['heading'=>'Visible hero','body'=>'Initial public body'];
$r=$service::transition($r,'add:faq',$d);$d=$r['draft'];$d['sections'][1]['content']=['heading'=>'Fixture question','body'=>'**Fixture answer**'];
$r=$service::transition($r,'save',$d);$r=$service::transition($r,'publish');
$d=$service::editDocument($r);$d['sections'][0]['content']['body']='Private draft body';
$r=$service::transition($r,'save',$d);
$pageTitle='Reusable page fixture';$metaDescription='';$metaRobots='noindex';$seo=[];
if($path==='/admin/pages'){
    $_SESSION['user']=['role'=>'admin','email'=>'fixture@example.test'];
    $pages=[['slug'=>'fixture','title'=>$r['draft']['title'],'published'=>true,'draft'=>true]];
    $editor=['state'=>$r,'revision'=>App\Services\ResourceService::revision($r)];$pageError='';$schemaReady=true;
    $viewFile=app_path('views/admin/cms-pages.php');require app_path('views/layouts/admin.php');
}else{
    $document=$service::publishedDocument($r);$viewFile=app_path('views/public/cms-page.php');require app_path('views/layouts/app.php');
}
