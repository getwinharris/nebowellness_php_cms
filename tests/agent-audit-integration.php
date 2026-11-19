<?php
declare(strict_types=1);

use App\Services\{AuditLogService, DatabaseService};

if (PHP_SAPI !== 'cli' || !in_array(getenv('BAPX_MYSQL_HOST'), ['localhost', '127.0.0.1'], true)
    || !preg_match('/(?:_test|_fixture)$/D', (string)getenv('BAPX_MYSQL_DB')) || getenv('BAPX_TEST_MODE') === '1') {
    fwrite(STDERR, "Set a disposable local BAPX_MYSQL_* connection to a _test or _fixture database.\n");
    exit(2);
}
require __DIR__ . '/../app/bootstrap.php';

$db = new DatabaseService(true);
$audit = new AuditLogService($db);
$now = strtotime('2037-03-20T12:00:00Z');
$weekStart = strtotime('2037-03-14T00:00:00Z');
$prefix = 'ae-' . bin2hex(random_bytes(6)) . '-';

$store = static function (string $suffix, int $when, string $event = 'agent.run', bool $traced = true) use ($db, $prefix): void {
    $id = $prefix . $suffix;
    $meta = ['surface'=>'admin', 'outcome'=>'model', 'duration_ms'=>23];
    if ($traced) $meta['trace_id'] = $id;
    $db->upsert('audit_events', ['id'=>$id, 'event'=>$event, 'meta'=>$meta, 'created_at'=>gmdate('c', $when)]);
};
$db->query('START TRANSACTION');
try {
    $store('before-week', $weekStart - 1);
    $store('first-second', $weekStart);
    $store('unrelated', $now, 'contact.save');
    $store('untraced', $now, traced: false);
    $store('future', $now + 1);
    for ($i = 0; $i < 15; $i++) $store('trace-' . $i, $now - $i);

    $week = $audit->dashboardForPeriod(7, $now);
    if ($week['surfaces']['admin']['requests'] !== 17 || $week['daily']['2037-03-14']['runs'] !== 1) {
        throw new RuntimeException('Indexed range must include the UTC start, untraced run and final second only once.');
    }
    if ($audit->dashboardForPeriod(30, $now)['surfaces']['admin']['requests'] !== 18) {
        throw new RuntimeException('The wider window must include the earlier run.');
    }
    $traces = $audit->latestAgentTraces($now);
    $expected = array_map(static fn(int $i): string => $prefix . 'trace-' . $i, range(0, 11));
    if (array_column($traces, 'id') !== $expected) {
        throw new RuntimeException('The latest 12 traced agent runs must exclude unrelated, future and untraced records before applying the limit.');
    }
    echo "PASS indexed agent windows and latest trace ordering\n";
} finally {
    $db->query('ROLLBACK');
}
