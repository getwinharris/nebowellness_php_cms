<?php
namespace App\Services;

/** Request-local metadata only. Never stores prompts, tool arguments or results. */
final class AgentRunTrace
{
    public readonly string $id;
    private array $steps = [];
    private ?int $lastProviderStep = null;

    public function __construct()
    {
        $this->id = bin2hex(random_bytes(8));
    }

    public function provider(string $provider, string $model, int $status, int $transportError, float $started): void
    {
        $this->lastProviderStep = count($this->steps) < 16 ? count($this->steps) : null;
        $this->add([
            'kind' => 'provider',
            'name' => in_array($provider, ['google', 'openai', 'anthropic'], true) ? $provider : 'compatible',
            'model' => preg_match('#^[a-zA-Z0-9._:/-]{1,100}$#D', $model) ? $model : 'custom',
            'status' => $status,
            'outcome' => $transportError ? 'transport_error' : ($status === 200 ? 'http_ok' : 'http_error'),
        ], $started);
    }

    public function markProviderResponseUnusable(): void
    {
        $last = $this->lastProviderStep;
        if ($last !== null && $this->steps[$last]['kind'] === 'provider' && $this->steps[$last]['outcome'] === 'http_ok') {
            $this->steps[$last]['outcome'] = 'unusable_response';
        }
    }

    public static function providerFailed(string $outcome): bool
    {
        return in_array($outcome, ['http_error', 'transport_error', 'unusable_response'], true);
    }

    public function tool(string $name, string $outcome, float $started): void
    {
        $this->add(['kind' => 'tool', 'name' => $name, 'outcome' => $outcome], $started);
    }

    private function add(array $step, float $started): void
    {
        if (count($this->steps) >= 16) return;
        $step['duration_ms'] = max(0, (int) round((microtime(true) - $started) * 1000));
        $this->steps[] = $step;
    }

    public function metadata(): array
    {
        return ['trace_id' => $this->id, 'steps' => $this->steps];
    }
}
