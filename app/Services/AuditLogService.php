<?php
namespace App\Services;

final class AuditLogService {
    private const OUTCOMES = ['model', 'fallback', 'private_account', 'draft', 'manual', 'draft_unavailable', 'error'];
    public function __construct(private DatabaseService $store = new DatabaseService()) {}

    public function all(): array {
        return array_reverse($this->store->read('audit_events'));
    }

    /** Operational metadata only: never retain prompts, replies, identities or secrets. */
    public function agentRun(string $surface, string $outcome, float $startedAt, ?AgentRunTrace $trace = null): void {
        if (!in_array($surface, ['support', 'admin'], true)) return;
        if (!in_array($outcome, self::OUTCOMES, true)) return;
        $this->record('run', 'agent', $surface, [
            'surface' => $surface,
            'outcome' => $outcome,
            'duration_ms' => max(0, (int)round((microtime(true) - $startedAt) * 1000)),
        ] + ($trace?->metadata() ?? []), ['email' => 'system', 'name' => 'Agent monitoring']);
    }

    public static function agentSummary(array $events, ?int $now = null, ?int $cutoff = null): array {
        $now ??= time();
        $cutoff ??= $now - 86400;
        $summary = [];
        $durations = ['support' => [], 'admin' => []];
        foreach (['support', 'admin'] as $surface) {
            $summary[$surface] = ['requests' => 0, 'model' => 0, 'fallback' => 0, 'private_account' => 0, 'draft' => 0, 'manual' => 0, 'draft_unavailable' => 0, 'error' => 0, 'duration_ms' => 0, 'last_seen' => ''];
        }
        foreach ($events as $event) {
            if (($event['event'] ?? '') !== 'agent.run') continue;
            $meta = $event['meta'] ?? [];
            if (!is_array($meta)) continue;
            $surface = $meta['surface'] ?? '';
            $outcome = $meta['outcome'] ?? '';
            $created = (string)($event['created_at'] ?? '');
            $timestamp = strtotime($created);
            if (!isset($summary[$surface]) || !in_array($outcome, self::OUTCOMES, true) || $timestamp === false || $timestamp < $cutoff || $timestamp > $now) continue;
            $summary[$surface]['requests']++;
            $summary[$surface][$outcome]++;
            $duration = max(0, (int)($meta['duration_ms'] ?? 0));
            $summary[$surface]['duration_ms'] += $duration;
            $durations[$surface][] = $duration;
            if ($created > $summary[$surface]['last_seen']) $summary[$surface]['last_seen'] = $created;
        }
        foreach ($summary as $surface => &$row) {
            $count = $row['requests'];
            $row['average_ms'] = $count ? (int)round($row['duration_ms'] / $count) : null;
            $row['error_percent'] = $count ? round(100 * $row['error'] / $count, 1) : null;
            sort($durations[$surface], SORT_NUMERIC);
            $row['p95_ms'] = $count ? $durations[$surface][(int)ceil(.95 * $count) - 1] : null;
        }
        return $summary;
    }

    public static function agentDashboard(array $events, int $days, ?int $now = null): array {
        $now ??= time();
        $days = $days === 30 ? 30 : 7;
        $start = intdiv($now, 86400) * 86400 - ($days - 1) * 86400;
        $daily = [];
        for ($day = $start; $day <= $now; $day += 86400) {
            $daily[gmdate('Y-m-d', $day)] = ['runs' => 0, 'errors' => 0];
        }
        $providers = ['support' => ['attempts' => 0, 'failures' => 0], 'admin' => ['attempts' => 0, 'failures' => 0]];
        foreach ($events as $event) {
            $meta = $event['meta'] ?? [];
            $surface = $meta['surface'] ?? '';
            $at = strtotime((string)($event['created_at'] ?? ''));
            if (($event['event'] ?? '') !== 'agent.run' || !isset($providers[$surface])
                || !in_array($meta['outcome'] ?? '', self::OUTCOMES, true)
                || $at === false || $at < $start || $at > $now) continue;
            $key = gmdate('Y-m-d', $at);
            $daily[$key]['runs']++;
            if ($meta['outcome'] === 'error') $daily[$key]['errors']++;
            foreach (array_slice(is_array($meta['steps'] ?? null) ? $meta['steps'] : [], 0, 16) as $step) {
                $outcome = $step['outcome'] ?? '';
                if (($step['kind'] ?? '') !== 'provider' || !in_array($outcome, ['http_ok', 'http_error', 'transport_error'], true)) continue;
                $providers[$surface]['attempts']++;
                if ($outcome !== 'http_ok') $providers[$surface]['failures']++;
            }
        }
        return ['surfaces' => self::agentSummary($events, $now, $start), 'daily' => $daily, 'providers' => $providers, 'as_of' => $now];
    }

    public function record(string $action, string $resource, string $resourceId = '', array $details = [], ?array $actor = null): array {
        $actor ??= $_SESSION['user'] ?? [];
        $event = [
            'id' => bin2hex(random_bytes(8)),
            'event' => trim($resource . '.' . $action, '.'),
            'action' => $action,
            'resource' => $resource,
            'resource_id' => $resourceId,
            'status' => 'recorded',
            'actor_email' => $actor['email'] ?? 'system',
            'actor_name' => $actor['name'] ?? '',
            'meta' => $details,
            'created_at' => date('c'),
        ];
        // Insert just this event. The previous implementation read the whole table,
        // appended one row and rewrote every row, which is O(n) per admin action and
        // risks losing the entire audit history if the rewrite fails part-way through.
        // Audit logging must never break the operation it is recording.
        try {
            $this->store->upsert('audit_events', $event);
        } catch (\Throwable $e) {
            error_log('Audit log write failed: ' . $e->getMessage());
        }
        return $event;
    }
}
