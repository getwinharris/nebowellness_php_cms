<?php
declare(strict_types=1);

use App\Services\{AgentRunTrace, AgentToolRegistry, AiClient, AiReplyCleaner, SupportBotService};

if (PHP_SAPI!=='cli'||getenv('BAPX_TEST_MODE')!=='1') exit(2);
require __DIR__.'/../../app/bootstrap.php';
$trace=new AgentRunTrace();
$registry=new AgentToolRegistry(modules:['shop'=>false,'blog'=>false,'campaigns'=>true],scope:'support',
    contextLoader:static fn():array=>['fixture_value'=>'QA-ORCHID-731','scope'=>'public synthetic record'],question:'fixture value');
$answer=(new AiClient(routingScope:'support',trace:$trace))->completeWithTools('Retrieve the synthetic fixture value using clinic_context. Then answer the exact value.',$registry,256,0.1,4);
$clean=(new AiReplyCleaner())->clean($answer,'');
$usable=!AiClient::isError($answer)&&$trace->hasNativeToolAnswer()&&(new AiReplyCleaner())->usableAnswer($clean,'What is the fixture value?');
echo json_encode(['usable'=>$usable,'answer'=>$usable?$clean:null,'trace'=>$trace->metadata(),'error'=>AiClient::isError($answer)],JSON_THROW_ON_ERROR);
