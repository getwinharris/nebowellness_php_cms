<?php
declare(strict_types=1);
namespace App\Services;

/** A diagnostic-only capability with no database, account or business dependency. */
final class ProviderProbeTools implements ModelToolSet
{
    private int $calls=0;
    private int $successes=0;
    public function __construct(private readonly string $challenge) {}

    public function beginInferenceAttempt(): void { $this->calls=0; $this->successes=0; }

    public function googleDeclarations(): array {
        return [['name'=>'connection_probe','description'=>'Return the synthetic diagnostic challenge. Takes no arguments.']];
    }

    public function completionDeclarations(): array {
        return [['type'=>'function','function'=>$this->googleDeclarations()[0]+[
            'strict'=>true,
            'parameters'=>['type'=>'object','properties'=>(object)[],'required'=>[],'additionalProperties'=>false],
        ]]];
    }

    public function executeModelCall(string $name,array $args,?AgentRunTrace $trace,string $callId=''): array {
        $started=microtime(true);
        $this->calls++;
        $valid=$name==='connection_probe'&&$args===[]&&$this->calls===1;
        if ($valid) $this->successes++;
        $trace?->tool($name==='connection_probe'?'connection_probe':'rejected',$valid?'ok':'error',$started,'model',$callId);
        return $valid?['challenge'=>$this->challenge]:['error'=>'Invalid or repeated diagnostic tool call.'];
    }
    public function successfulCalls(): int { return $this->successes; }
}
