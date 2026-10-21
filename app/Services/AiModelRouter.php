<?php
namespace App\Services;

/** Persistent, shared failover state for the two Google-hosted Gemma models. */
final class AiModelRouter
{
    public const PRIMARY = 'gemma-4-31b-it';
    public const FALLBACK = 'gemma-4-26b-a4b-it';
    private const STATE_ID = 'google-gemma-agent';
    private const FAILURE_LIMIT = 3;

    public function __construct(private DatabaseService $store = new DatabaseService()) {}

    /** The configured primary stays authoritative; other configured models are untouched. */
    public function modelFor(string $configuredModel): string
    {
        if ($configuredModel !== self::PRIMARY) return $configuredModel;
        try {
            $state = $this->state();
            $active = (string)($state['active_model'] ?? self::PRIMARY);
            return in_array($active, [self::PRIMARY, self::FALLBACK], true) ? $active : self::PRIMARY;
        } catch (\Throwable) {
            return self::PRIMARY;
        }
    }

    public function recordSuccess(string $model): string
    {
        return $this->record($model, true);
    }

    /** Returns the model that should receive the next request. */
    public function recordFailure(string $model): string
    {
        return $this->record($model, false);
    }

    private function record(string $model, bool $success): string
    {
        if (!in_array($model, [self::PRIMARY, self::FALLBACK], true)) return $model;
        try {
            $next = self::transition($this->state(), $model, $success);
            $next['id'] = self::STATE_ID;
            $next['updated_at'] = date('c');
            $this->store->upsert('ai_model_state', $next, 'id');
            return (string)$next['active_model'];
        } catch (\Throwable) {
            // Routing telemetry must never turn an otherwise useful model response into
            // a 500. If state storage is unavailable, keep using the current model.
            return $model;
        }
    }

    /** @return array<string,mixed> */
    private function state(): array
    {
        return $this->store->find('ai_model_state', self::STATE_ID) ?? [
            'id' => self::STATE_ID,
            'active_model' => self::PRIMARY,
            'primary_failures' => 0,
            'fallback_failures' => 0,
        ];
    }

    /** Pure transition used by tests and by the persistent router. */
    public static function transition(array $state, string $model, bool $success): array
    {
        $state += ['active_model' => self::PRIMARY, 'primary_failures' => 0, 'fallback_failures' => 0];
        $counter = $model === self::FALLBACK ? 'fallback_failures' : 'primary_failures';
        if ($success) {
            $state[$counter] = 0;
            $state['active_model'] = $model;
            return $state;
        }

        $state[$counter] = (int)$state[$counter] + 1;
        if ((int)$state[$counter] >= self::FAILURE_LIMIT) {
            $state[$counter] = 0;
            $state['active_model'] = $model === self::PRIMARY ? self::FALLBACK : self::PRIMARY;
        } else {
            $state['active_model'] = $model;
        }
        return $state;
    }
}
