<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli'||getenv('BAPX_TEST_MODE')!=='1') exit(2);
require __DIR__.'/../../app/bootstrap.php';
$csrf='fixture-token';$title='Blog';$posts=[];$categories=[];$mediaFiles=[];$currentAuthor='Fixture Owner';
echo '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/css/band.css"></head><body>';
require app_path('views/admin/blog.php');
echo '</body></html>';
