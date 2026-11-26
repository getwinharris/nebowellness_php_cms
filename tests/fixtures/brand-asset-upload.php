<?php
declare(strict_types=1);
// Never available on deployed hosting. Only a disposable loopback fixture.
if (PHP_SAPI!=='cli-server'||getenv('BAPX_TEST_MODE')!=='1') { http_response_code(404); exit; }
require __DIR__.'/../../app/bootstrap.php';
header('Content-Type: application/json');
try {
    $dir=getenv('BRAND_FIXTURE_DIRECTORY');
    if (!is_string($dir)||!is_dir($dir)) throw new RuntimeException('Fixture storage unavailable.');
    $service=new App\Services\BrandAssetService($dir);
    $patch=App\Services\BrandAssetService::palettePatch([], $_POST);
    $pending=[];
    foreach (['logo','favicon'] as $kind) $pending[$kind]=App\Services\BrandAssetService::pending($kind,$_FILES[$kind.'_file']??null);
    foreach ($pending as $kind=>$upload) if ($upload!==null) $patch[$kind.'_url']=$service->store($kind,$upload);
    echo json_encode(['patch'=>$patch],JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $error) {
    http_response_code(422);
    echo json_encode(['error'=>$error->getMessage()],JSON_THROW_ON_ERROR);
}
