<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || getenv('BAPX_TEST_MODE') !== '1') exit(2);
$request = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
session_save_path($argv[1]);
session_id($argv[2]);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = 'fixture-' . $argv[2];
$_SERVER['REQUEST_URI'] = $request['surface'] === 'admin' ? '/admin/agent/ask' : '/support/ask';
require __DIR__ . '/../../app/bootstrap.php';
$_SESSION['csrf_token'] = 'fixture-token';
if (isset($request['identity'])) $_SESSION['user'] = ['role' => 'admin', 'email' => $request['identity'] . '@example.test'];
if (empty($request['fields'])) {
    echo json_encode((new App\Services\AgentConversation($request['surface']))->snapshot(), JSON_THROW_ON_ERROR);
    exit;
}
$_POST = $request['fields'];
ob_start(static function (string $body): string {
    return json_encode(['status' => http_response_code(), 'body' => json_decode($body, true)], JSON_THROW_ON_ERROR);
});
(new App\Router(require app_path('app/routes.php')))->dispatch('POST', $_SERVER['REQUEST_URI']);
