<?php
namespace App\Services;

final class AuditLogService {
    private const OUTCOMES = ['model', 'fallback', 'private_account', 'draft', 'manual', 'draft_unavailable', 'error'];
    public function __construct(private DatabaseService $store = new DatabaseService()) {}

    public function all(): array {
        return array_reverse($this->store->read('audit_events'));
    }

    /** Read only agent runs in the requested time window, using the clinic-local DATETIME index. */
    public function agentRunsBetween(int $start, int $end): array {
        return $this->agentRows(
            "AND _created_at BETWEEN ? AND ?",
            [date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end)]
        );
    }

    public function latestAgentTraces(int $now): array {
        return $this->agentRows(
            "AND _created_at <= ? AND JSON_TYPE(JSON_EXTRACT(_data, '$.meta.trace_id')) = 'STRING'
             ORDER BY _created_at DESC, id DESC LIMIT 12",
            [date('Y-m-d H:i:s', $now)]
        );
    }

    private function agentRows(string $filter, array $parameters): array {
        $rows = $this->store->query(
            "SELECT _data FROM audit_events WHERE JSON_UNQUOTE(JSON_EXTRACT(_data, '$.event')) = 'agent.run' " . $filter,
            $parameters
        );
        return array_map(static fn(array $row): array => json_decode($row['_data'], true, 512, JSON_THROW_ON_ERROR), $rows);
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
            if (!is_string($surface) || !isset($summary[$surface]) || !in_array($outcome, self::OUTCOMES, true) || $timestamp === false || $timestamp < $cutoff || $timestamp > $now) continue;
            $summary[$surface]['requests']++;
            $summary[$surface][$outcome]++;
            $duration = max(0, (int)($meta['duration_ms'] ?? 0));
            $summary[$surface]['duration_ms'] += $duration;
            $durations[$surface][] = $duration;
            if ($timestamp > (strtotime($summary[$surface]['last_seen']) ?: 0)) $summary[$surface]['last_seen'] = $created;
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

    public function dashboardForPeriod(int $days, ?int $now = null): array {
        $now ??= time();
        return self::agentDashboard($this->agentRunsBetween(self::periodStart($days, $now), $now), $days, $now);
    }

    private static function periodStart(int $days, int $now): int {
        $days = $days === 30 ? 30 : 7;
        return intdiv($now, 86400) * 86400 - ($days - 1) * 86400;
    }

    public static function agentDashboard(array $events, int $days, ?int $now = null): array {
        $now ??= time();
        $start = self::periodStart($days, $now);
        $daily = [];
        for ($day = $start; $day <= $now; $day += 86400) {
            $daily[gmdate('Y-m-d', $day)] = ['runs' => 0, 'errors' => 0];
        }
        $providers = ['support' => ['attempts' => 0, 'failures' => 0], 'admin' => ['attempts' => 0, 'failures' => 0]];
        $providerDetails = [];
        foreach ($events as $event) {
            $meta = $event['meta'] ?? [];
            if (!is_array($meta)) continue;
            $surface = $meta['surface'] ?? '';
            $at = strtotime((string)($event['created_at'] ?? ''));
            if (($event['event'] ?? '') !== 'agent.run' || !is_string($surface) || !isset($providers[$surface])
                || !in_array($meta['outcome'] ?? '', self::OUTCOMES, true)
                || $at === false || $at < $start || $at > $now) continue;
            $key = gmdate('Y-m-d', $at);
            $daily[$key]['runs']++;
            if ($meta['outcome'] === 'error') $daily[$key]['errors']++;
            foreach (array_slice(is_array($meta['steps'] ?? null) ? $meta['steps'] : [], 0, 16) as $step) {
                if (!is_array($step)) continue;
                $outcome = $step['outcome'] ?? '';
                if (($step['kind'] ?? '') !== 'provider' || !is_string($outcome) || ($outcome !== 'http_ok' && !AgentRunTrace::providerFailed($outcome))) continue;
                $providers[$surface]['attempts']++;
                if ($outcome !== 'http_ok') $providers[$surface]['failures']++;
                self::addProviderAttempt($providerDetails, $surface, $step);
            }
        }
        foreach ($providerDetails as &$row) {
            sort($row['durations'], SORT_NUMERIC);
            $timed = count($row['durations']);
            $row['p95_ms'] = $timed ? $row['durations'][(int)ceil(.95 * $timed) - 1] : null;
            unset($row['durations']);
            arsort($row['failure_types']);
        }
        unset($row);
        uasort($providerDetails, static fn(array $a, array $b): int =>
            [$b['failures'], $b['attempts']] <=> [$a['failures'], $a['attempts']]
            ?: [$a['surface'], $a['provider'], $a['model'], $a['mode']] <=> [$b['surface'], $b['provider'], $b['model'], $b['mode']]);
        return ['surfaces' => self::agentSummary($events, $now, $start), 'daily' => $daily, 'providers' => $providers,
            'provider_details' => array_values($providerDetails), 'as_of' => $now];
    }

    private static function addProviderAttempt(array &$groups, string $surface, array $step): void {
        $provider = in_array($step['name'] ?? null, ['google', 'openai', 'anthropic', 'compatible'], true) ? $step['name'] : 'unknown';
        $model = isset($step['model']) ? AgentRunTrace::modelLabel($step['model']) : 'unknown';
        $mode = in_array($step['mode'] ?? null, ['plain', 'tools'], true) ? $step['mode'] : 'unknown';
        $key = implode('|', [$surface, $provider, $model, $mode]);
        $groups[$key] ??= compact('surface', 'provider', 'model', 'mode') + [
            'attempts' => 0, 'failures' => 0, 'durations' => [], 'failure_types' => [],
        ];
        $row = &$groups[$key];
        $row['attempts']++;
        if (isset($step['duration_ms']) && is_numeric($step['duration_ms']) && $step['duration_ms'] >= 0) {
            $row['durations'][] = (int)$step['duration_ms'];
        }
        if ($step['outcome'] === 'http_ok') return;
        $row['failures']++;
        $status = (int)($step['status'] ?? 0);
        $reason = match ($step['outcome']) {
            'http_error' => $status >= 100 && $status < 600 ? 'HTTP ' . $status : 'HTTP error',
            'timeout' => 'Timeout',
            'transport_error' => 'Transport error',
            'unusable_response' => 'Unusable response',
        };
        $row['failure_types'][$reason] = ($row['failure_types'][$reason] ?? 0) + 1;
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
