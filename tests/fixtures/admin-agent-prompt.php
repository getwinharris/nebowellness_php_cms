<?php
// Isolated subprocess: replaces only the database and provider classes before autoload.
namespace App\Services {
    final class DatabaseService {
        public function read(string $table): array {
            return match ($table) {
                'settings' => [['module_shop' => $GLOBALS['fixtureShop'] ? '1' : '0']],
                'users' => [['email' => 'private@example.test', 'name' => 'Private Person']],
                'orders' => [['customer_email' => 'private@example.test', 'status' => 'confirmed', 'total' => 100, 'items' => [['slug' => 'p', 'name' => 'Fixture Product', 'qty' => 1]]]],
                'products' => [['slug' => 'p', 'name' => 'Fixture Product']],
                default => [],
            };
        }
        public function upsert(string $table, array $record, string $key = 'id'): void {}
    }
    final class AiClient {
        public static function isError(string $answer): bool { return false; }
        public function completeWithTools(string $prompt, AgentToolRegistry $tools): string {
            file_put_contents($GLOBALS['fixtureOutput'], $prompt);
            return 'The fixture has no further information.';
        }
    }
}
namespace {
    $GLOBALS['fixtureShop'] = ($argv[1] ?? '') === 'on';
    $GLOBALS['fixtureOutput'] = $argv[2];
    putenv('AI_API_KEY=fixture');
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/admin/agent/ask';
    require __DIR__ . '/../../app/bootstrap.php';
    $_SESSION['user'] = ['role' => 'admin', 'email' => 'owner@example.test'];
    $_SESSION['csrf_token'] = 'fixture-token';
    $_POST = ['_csrf' => 'fixture-token', 'message' => 'What can you help me with?'];
    (new \App\Controllers\AdminController())->agentAsk();
}
