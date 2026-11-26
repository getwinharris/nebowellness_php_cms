<?php
namespace App\Services;

/** Request-local metadata only. Never stores prompts, tool arguments or results. */
final class AgentRunTrace
{
    public readonly string $id;
    private array $steps = [];
    private ?int $lastProviderStep = null;
    private ?string $outcome = null;
    private int $omittedSteps = 0;
    private array $sources = [];
    private ?string $latestProviderOutcome = null;
    private bool $toolAfterProvider = false;
    private array $currentSuccessfulTools = [];

    /** A failover starts a fresh model exchange. Keep earlier attempts for diagnostics,
     * but do not credit their tools or sources to an answer from another exchange. */
    public function beginInferenceAttempt(): void {
        $this->latestProviderOutcome = null;
        $this->toolAfterProvider = false;
        $this->currentSuccessfulTools = [];
        $this->sources = [];
    }

    public function hasCompletedInference(): bool {
        return $this->latestProviderOutcome === 'http_ok' && !$this->toolAfterProvider;
    }

    public function recordOutcome(string $outcome): void { $this->outcome = $outcome; }
    public function outcome(): ?string { return $this->outcome; }

    public function __construct()
    {
        $this->id = bin2hex(random_bytes(8));
    }

    public function provider(string $provider, string $model, int $status, int $transportError, float $started, bool $withTools = false): void
    {
        $this->lastProviderStep = count($this->steps) < 16 ? count($this->steps) : null;
        $this->toolAfterProvider = false;
        $this->latestProviderOutcome = match (true) {
            $transportError === 28 => 'timeout',
            $transportError !== 0 => 'transport_error',
            $status === 200 => 'http_ok',
            default => 'http_error',
        };
        $this->add([
            'kind' => 'provider',
            'name' => in_array($provider, ['google', 'openai', 'anthropic'], true) ? $provider : 'compatible',
            'model' => self::modelLabel($model),
            'mode' => $withTools ? 'tools' : 'plain',
            'status' => $status,
            'outcome' => $this->latestProviderOutcome,
        ], $started);
    }

    /** Keep configured model IDs usable for grouping, including organisation/model IDs. */
    public static function modelLabel(mixed $model): string
    {
        return is_string($model) && strlen($model) <= 100 &&
            preg_match('#^[a-zA-Z0-9][a-zA-Z0-9._-]*(?:/[a-zA-Z0-9][a-zA-Z0-9._-]*)?(?::[a-zA-Z0-9._-]+)?$#D', $model)
            ? $model : 'custom';
    }

    public function markProviderResponseUnusable(): void
    {
        if ($this->latestProviderOutcome==='http_ok') $this->latestProviderOutcome='unusable_response';
        $last = $this->lastProviderStep;
        if ($last !== null && $this->steps[$last]['kind'] === 'provider' && $this->steps[$last]['outcome'] === 'http_ok') {
            $this->steps[$last]['outcome'] = 'unusable_response';
        }
    }

    public static function providerFailed(string $outcome): bool
    {
        return in_array($outcome, ['http_error', 'transport_error', 'timeout', 'unusable_response'], true);
    }

    public function tool(string $name, string $outcome, float $started, string $origin='harness', string $callId=''): void
    {
        if ($origin==='model') {
            $this->toolAfterProvider=true;
            if ($outcome==='ok') $this->currentSuccessfulTools[$name]=true;
        }
        $step=['kind'=>'tool','name'=>$name,'outcome'=>$outcome,'origin'=>$origin==='model'?'model':'harness'];
        if ($callId!=='') $step['call_ref']=substr(hash('sha256',$callId),0,16);
        $this->add($step,$started);
    }

    private function add(array $step, float $started): void
    {
        if (count($this->steps) >= 16) { $this->omittedSteps++; return; }
        $step['duration_ms'] = max(0, (int) round((microtime(true) - $started) * 1000));
        $this->steps[] = $step;
    }

    public function hasSuccessfulInferenceAndTool(): bool {
        $provider=false; $tool=false;
        foreach ($this->steps as $step) {
            if ($step['kind']==='provider' && $step['outcome']==='http_ok') $provider=true;
            if ($step['kind']==='tool' && $step['outcome']==='ok') $tool=true;
        }
        return $provider && $tool;
    }

    /** Final visible inference must follow an actual model-requested, successful tool. */
    public function hasNativeToolAnswer(): bool {
        return $this->hasCompletedInference() && $this->currentSuccessfulTools!==[];
    }

    public function usedNativeTool(array $names): bool {
        return array_intersect($names,array_keys($this->currentSuccessfulTools))!==[];
    }

    public function source(string $path): void {
        if (!preg_match('#^/(?:\#(?:team|programs)|campaigns/[a-z0-9-]+|blog/[a-z0-9-]+|contact(?:\#consultation-fees)?|admin|admin/(?:support-tickets|contact-submissions|therapy|dashboard|consultants|campaigns|orders|products|blog))$#D',$path)) return;
        if (count($this->sources)<12) $this->sources[$path]=true;
    }

    public function sources(): array { return array_keys($this->sources); }

    public function usage(array $result): void {
        $index=$this->lastProviderStep;
        if ($index===null) return;
        $usage=$result['usage']??$result['usageMetadata']??[];
        if (!is_array($usage)) return;
        foreach (['input_tokens'=>['prompt_tokens','promptTokenCount'],'output_tokens'=>['completion_tokens','candidatesTokenCount'],'total_tokens'=>['total_tokens','totalTokenCount']] as $key=>$fields) {
            foreach ($fields as $field) if (isset($usage[$field]) && is_int($usage[$field]) && $usage[$field]>=0) {
                $this->steps[$index][$key]=$usage[$field]; break;
            }
        }
    }

    public function metadata(): array
    {
        return ['trace_id' => $this->id, 'steps' => $this->steps, 'omitted_steps' => $this->omittedSteps, 'sources'=>$this->sources()];
    }
}
