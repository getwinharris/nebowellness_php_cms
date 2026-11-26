<?php
declare(strict_types=1);
namespace App\Services;

/** Private, bounded session records. Never expose this collection through public tools. */
final class AgentSessionService {
    public const DAYS = 30;
    public const MAX_TURNS = 100;
    public const MAX_BYTES = 1048576;

    public function __construct(private DatabaseService $store = new DatabaseService()) {}

    public static function redact(string $text): string {
        // Best-effort credential minimisation; this is not a clinical data anonymizer.
        $text = preg_replace('/-----BEGIN [^-]*PRIVATE KEY-----[\s\S]*?-----END [^-]*PRIVATE KEY-----/i', '[redacted private key]', $text) ?? '';
        $text = preg_replace('/\b(?:Bearer\s+[^\s,;]+|sk-(?:proj-)?[A-Za-z0-9_-]{12,}|AIza[A-Za-z0-9_-]{25,})/i', '[redacted credential]', $text) ?? '';
        return preg_replace('/\b(password|passwd|api[_ -]?key|token|secret|otp|authorization)\s*[:=]\s*[^\s,;]+/i', '$1: [redacted]', $text) ?? '';
    }

    public static function event(string $question, array $response, int $status, AgentRunTrace $trace): array {
        $reply = $response['answer'] ?? $response['reply'] ?? $response['error'] ?? '';
        $draft = $response['draft'] ?? null;
        if (is_array($draft)) $reply = 'Draft response (' . (in_array($draft['mode'] ?? '', ['generated','manual','unavailable'], true) ? $draft['mode'] : 'other') . '). Editable draft contents are not archived.';
        return [
            'at'=>date('c'), 'question'=>self::redact($question),
            'reply'=>self::redact(is_string($reply) ? $reply : ''),
            'http_status'=>$status, 'outcome'=>$trace->outcome() ?? ($status >= 400 ? 'error' : 'unknown'),
            'response_mode'=>is_string($response['response_mode'] ?? null) ? $response['response_mode'] : '',
            'ticket_id'=>preg_match('/^[a-f0-9]{16,32}$/D',(string)($response['ticket_id']??'')) ? $response['ticket_id'] : null,
            'trace'=>$trace->metadata(),
        ];
    }

    public static function handoffNote(array $record, string $message): string {
        $events = $record['events'] ?? [];
        $last = end($events) ?: [];
        $tools = [];
        foreach ($events as $event) foreach ($event['trace']['steps'] ?? [] as $step) {
            if (($step['kind'] ?? '') === 'tool') $tools[] = $step['name'] . ' (' . $step['outcome'] . ')';
        }
        return "Customer requested clinic follow-up.\nRequest: " . self::redact($message)
            . "\nConversation turns recorded: " . count($events)
            . (!empty($record['omitted_turns']) ? " (some later turns exceeded archive limits)" : '')
            . (!empty($record['prior_history_unavailable']) ? " (earlier history was unavailable)" : '')
            . (!empty($last['reply']) ? "\nLast visible response: " . mb_substr((string)$last['reply'],0,800) : '')
            . "\nObserved tools: " . ($tools ? implode(', ',array_slice(array_unique($tools),0,12)) : 'None recorded')
            . "\nNext action: Review the request and respond through the support ticket. No appointment or resolution is confirmed.";
    }

    public function persist(array $record): void {
        if (!self::validId((string)($record['id'] ?? ''))) throw new \InvalidArgumentException('Invalid session.');
        $record['updated_at'] = date('c');
        $this->store->upsert('agent_sessions', $record);
    }

    public static function validId(string $id): bool { return (bool)preg_match('/^[a-f0-9]{32}$/D',$id); }

    public function get(string $id): ?array {
        if (!self::validId($id)) return null;
        $row = $this->store->find('agent_sessions',$id);
        return $row && strtotime((string)($row['updated_at'] ?? '')) >= time()-self::DAYS*86400 ? $row : null;
    }

    /** Metadata only in the list. Fetch one full transcript on explicit selection. */
    public function recent(): array {
        return $this->store->query("SELECT id, _updated_at AS updated_at, JSON_UNQUOTE(JSON_EXTRACT(_data,'$.surface')) AS surface,
            JSON_LENGTH(JSON_EXTRACT(_data,'$.events')) AS turns, JSON_UNQUOTE(JSON_EXTRACT(_data,'$.actor_type')) AS actor_type
            FROM agent_sessions WHERE _updated_at >= ? ORDER BY _updated_at DESC LIMIT 100", [date('Y-m-d H:i:s',time()-self::DAYS*86400)]);
    }

    /** Owner-triggered retention cleanup, limited per operation; no claim of an automatic scheduler. */
    public function purgeExpired(): int {
        $rows = $this->store->query("SELECT id FROM agent_sessions WHERE _updated_at < ? ORDER BY _updated_at LIMIT 100", [date('Y-m-d H:i:s',time()-self::DAYS*86400)]);
        $count = 0;
        foreach ($rows as $row) if (self::validId((string)$row['id'])) {
            $this->store->delete('agent_sessions',(string)$row['id']); $count++;
        }
        return $count;
    }
}
