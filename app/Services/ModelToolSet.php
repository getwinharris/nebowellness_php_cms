<?php
declare(strict_types=1);
namespace App\Services;

/** Transport-facing contract; the caller owns authorization and tool implementation. */
interface ModelToolSet
{
    public function beginInferenceAttempt(): void;
    public function googleDeclarations(): array;
    public function completionDeclarations(): array;
    public function executeModelCall(string $name,array $args,?AgentRunTrace $trace,string $callId=''): array;
}
