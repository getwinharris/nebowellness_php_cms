<?php
namespace App\Controllers;
use App\Services\{AgentRunTrace,AuditLogService,AuthService,SupportBotService,SupportTicketService};

final class SupportController extends BaseController {
    public function page(): void {
        $this->seoKey = 'support';
        $supportNav = $this->loadSupportNavigation();
        $this->render('public/support', ['supportNav' => $supportNav]);
    }

    public function ask(): void {
        $this->isApiRequest = true;
        $this->checkRateLimit('support', 10, 60);
        $startedAt = microtime(true);
        $trace = new AgentRunTrace();
        header('X-Agent-Trace: ' . $trace->id);
        try {
            $user = (new AuthService())->user();
            $message = is_string($_POST['message'] ?? null) ? trim($_POST['message']) : '';
            if ($message === '' || mb_strlen($message) > 2000) {
                $this->jsonResponse(['error' => 'Enter a message of 1 to 2,000 characters.'], 422);
                return;
            }
            if (($_POST['intent'] ?? '') === 'ticket') {
                $requestId = $_POST['request_id'] ?? null;
                $expected = $_SESSION['csrf_token'] ?? '';
                $submitted = $_POST['_csrf'] ?? null;
                if (!is_string($submitted) || $expected === '' || !hash_equals($expected, $submitted)) {
                    $this->jsonResponse(['error' => 'Refresh this page before sending a ticket.'], 419);
                    return;
                }
                if (empty($user['email'])) {
                    $this->jsonResponse(['error' => 'Sign in before sending a support ticket.'], 401);
                    return;
                }
                if (!is_string($requestId) || !preg_match('/^[a-f0-9]{32}$/D', $requestId)) {
                    $this->jsonResponse(['error' => 'Refresh the page and try again.'], 422);
                    return;
                }
                $ticket = (new SupportTicketService())->create((string)$user['email'], $message, 'support chat', $requestId);
                $trace->tool('create_support_ticket', 'ok', $startedAt);
                (new AuditLogService())->agentRun('support', 'fallback', $startedAt, $trace);
                $this->jsonResponse(['reply' => 'Sent to the clinic team. Track ticket ' . $ticket['id'] . ' in this chat.', 'ticket_id' => $ticket['id']]);
                return;
            }
            $answer = (new SupportBotService(trace: $trace))->answer($message, $user);
            $this->jsonResponse($answer);
        } catch (\Throwable $e) {
            (new AuditLogService())->agentRun('support', 'error', $startedAt, $trace);
            $this->jsonResponse(['error' => 'Support is temporarily unavailable. Please try again or contact the clinic.'], 503);
        }
    }

    private function loadSupportNavigation(): array {
        $file = app_path('content/support-navigation.yaml');
        if (!is_file($file)) return [];
        $yaml = @file_get_contents($file);
        if ($yaml === false || $yaml === '') return [];
        $sections = [];
        $current = null;
        foreach (explode("\n", $yaml) as $line) {
            if (preg_match('/^\s*-\s*section:\s*(.+)$/', $line, $m)) {
                if ($current) $sections[] = $current;
                $current = ['section' => trim($m[1]), 'links' => []];
            } elseif ($current && preg_match('/^\s+-\s+(\S+):\s*(.+)$/', $line, $m)) {
                $path = trim($m[1]);
                if (preg_match('#^/(shop|cart|checkout|account)(/|$)#', $path) && !module_on('shop')) continue;
                if (preg_match('#^/campaigns(/|$)#', $path) && !module_on('campaigns')) continue;
                if (preg_match('#^/blog(/|$)#', $path) && !module_on('blog')) continue;
                $current['links'][] = ['path' => $path, 'label' => trim($m[2])];
            }
        }
        if ($current) $sections[] = $current;
        return array_values(array_filter($sections, static fn(array $section): bool => $section['links'] !== []));
    }
}
