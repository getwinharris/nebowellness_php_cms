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
$answer = $surface === 'admin'
    ? $client->completeWithTools($prompt, new AgentToolRegistry(), maxRounds: $argv[2] === 'last-round' ? 1 : 4)
    : $client->complete($prompt);
$outcome = AiClient::isError($answer) || $answer === '' ? 'fallback' : 'model';
$event = ['event' => 'agent.run', 'created_at' => date('c'), 'meta' => $trace->metadata() + [
    'surface' => $surface, 'outcome' => $outcome, 'duration_ms' => (int)((microtime(true) - $started) * 1000),
]];
echo json_encode(['trace' => $trace->metadata(), 'report' => AuditLogService::agentDashboard([$event], 7)], JSON_THROW_ON_ERROR);
