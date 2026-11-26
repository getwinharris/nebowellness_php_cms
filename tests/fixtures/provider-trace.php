<?php
declare(strict_types=1);

use App\Services\{AgentRunTrace, AgentToolRegistry, AiClient, AuditLogService};

if (PHP_SAPI !== 'cli' || getenv('BAPX_TEST_MODE') !== '1') exit(2);
require __DIR__ . '/../../app/bootstrap.php';

$surface = $argv[1];
$trace = new AgentRunTrace();
$started = microtime(true);
$client = new AiClient(routingScope: $surface, trace: $trace);
$prompt = 'Synthetic confidential question';
$tools=new AgentToolRegistry(modules:['shop'=>false,'blog'=>true,'campaigns'=>true],scope:$surface,
    contextLoader:static fn()=>['fixture'=>'Public synthetic data']);
if ($argv[2]==='short-deadline') {
    // Exercise the real HTTP transport with less time than retry backoff requires.
    $post=new ReflectionMethod(AiClient::class,'post');
    $status=0;
    $endpoint=rtrim((string)getenv('BAPX_AI_ENDPOINT'),'/').'/fixture-unavailable:generateContent';
    $args=[$endpoint,'{"contents":[]}', ['Content-Type: application/json'], &$status, 1, 'google', 'fixture-unavailable', true];
    $post->invokeArgs($client,$args);
    echo json_encode(['status'=>$status,'trace'=>$trace->metadata(),'elapsed_ms'=>(int)((microtime(true)-$started)*1000)],JSON_THROW_ON_ERROR);
    exit;
}
$answer=$client->completeWithTools($prompt,$tools,maxRounds:$argv[2]==='last-round'?1:4);
$outcome=AiClient::isError($answer)||$answer===''||!$trace->hasNativeToolAnswer()?'error':'model';
$event = ['event' => 'agent.run', 'created_at' => date('c'), 'meta' => $trace->metadata() + [
    'surface' => $surface, 'outcome' => $outcome, 'duration_ms' => (int)((microtime(true) - $started) * 1000),
]];
echo json_encode(['trace' => $trace->metadata(), 'report' => AuditLogService::agentDashboard([$event], 7)], JSON_THROW_ON_ERROR);
