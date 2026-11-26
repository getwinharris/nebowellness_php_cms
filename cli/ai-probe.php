<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit(1); }
require __DIR__.'/../app/bootstrap.php';
if (count($argv)>3) {
    fwrite(STDERR,"Usage: ai:probe [text|tools] [configured-model]\n");exit(2);
}
try {
    $result=(new App\Services\AiClient())->probe($argv[1]??'text',$argv[2]??'');
} catch (Throwable) {
    $result=['ok'=>false,'reason'=>'diagnostic_unavailable','message'=>'Unable to read diagnostic configuration.'];
}
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
exit($result['ok']?0:1);
