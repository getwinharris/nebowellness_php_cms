#!/usr/bin/env php
<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit(1);}
require dirname(__DIR__).'/app/bootstrap.php';
echo json_encode((new App\Services\BlogService())->publicationReport(),JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
