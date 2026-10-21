<?php
namespace App\Services;

/** Persistent, shared failover state for the two Google-hosted Gemma models. */
final class AiModelRouter
{
    public const PRIMARY = 'gemma-4-31b-it';
    public const FALLBACK = 'gemma-4-26b-a4b-it';
    private const FAILURE_LIMIT = 3;

    public function __construct(
        private DatabaseService $store = new DatabaseService(),
        private string $scope = 'admin'
    ) {}

    /** The configured primary stays authoritative; other configured models are untouched. */
    public function modelFor(string $configuredModel, string $fingerprint = ''): string
    {
        if ($configuredModel !== self::PRIMARY) return $configuredModel;
        $state = $this->state();
        if ($fingerprint !== '' && !hash_equals((string)($state['config_fingerprint'] ?? ''), $fingerprint)) {
            $state = self::initialState($this->stateId(), $fingerprint);
            $this->store->upsert('ai_model_state', $state, 'id');
        }
        $active = (string)($state['active_model'] ?? self::PRIMARY);
        return in_array($active, [self::PRIMARY, self::FALLBACK], true) ? $active : self::PRIMARY;
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
            // Compare the revision that was read with the revision still in storage.
            // The hosted bridge implements the same conditional update, so concurrent
            // requests cannot silently overwrite one another's failure increment.
            for ($attempt = 0; $attempt < 3; $attempt++) {
                $current = $this->state();
                $next = self::transition($current, $model, $success);
                $next['id'] = $this->stateId();
                $next['updated_at'] = date('c');
                try {
                    $this->store->upsertIfRevision('ai_model_state', $next, ResourceService::revision($current));
                    return (string)$next['active_model'];
                } catch (\DomainException) {
                    continue;
                }
            }
            throw new \RuntimeException('Concurrent routing updates did not settle.');
        } catch (\Throwable $error) {
            // Routing telemetry must never turn an otherwise useful model response into
            // a 500. If state storage is unavailable, keep using the current model.
            error_log('AI model routing state could not be saved: ' . $error->getMessage());
            return $model;
        }
    }

    /** @return array<string,mixed> */
    private function state(): array
    {
        return $this->store->find('ai_model_state', $this->stateId()) ?? [
            'id' => $this->stateId(),
            'active_model' => self::PRIMARY,
            'primary_failures' => 0,
            'fallback_failures' => 0,
        ];
    }

    private function stateId(): string
    {
        return 'google-gemma-' . (preg_replace('/[^a-z0-9_-]+/i', '-', $this->scope) ?: 'admin');
    }

    /** @return array<string,mixed> */
    public static function initialState(string $id, string $fingerprint): array
    {
        return ['id'=>$id, 'active_model'=>self::PRIMARY, 'primary_failures'=>0, 'fallback_failures'=>0, 'config_fingerprint'=>$fingerprint, 'updated_at'=>date('c')];
    }

    /** Pure transition used by tests and by the persistent router. */
    public static function transition(array $state, string $model, bool $success): array
    {
        $state += ['active_model' => self::PRIMARY, 'primary_failures' => 0, 'fallback_failures' => 0];
        // Another request may already have switched the route. A late completion from
        // the old model must not undo that decision.
        if (($state['active_model'] ?? self::PRIMARY) !== $model) return $state;
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
