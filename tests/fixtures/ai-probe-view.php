<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli'||getenv('BAPX_TEST_MODE')!=='1') exit(2);
require __DIR__.'/../../app/bootstrap.php';
$csrf='fixture-token';$secrets=[];$googleCallbackUri='https://fixture.test/auth/google/callback';
$probeTargets=['gemma-4-31b-it','gemma-4-26b-a4b-it'];
$_SESSION['ai_test_result']=['ok'=>false,'model'=>'gemma-4-26b-a4b-it','mode'=>'tools',
    'message'=>'The provider returned a temporary server error.','duration_ms'=>300,
    'tool_calls'=>0,'trace_id'=>'0123456789abcdef',
    'steps'=>[['kind'=>'provider','name'=>'google','outcome'=>'http_error','status'=>503,'duration_ms'=>300]]];
echo '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/css/band.css"></head><body>';
require app_path('views/admin/integrations.php');
echo '</body></html>';
