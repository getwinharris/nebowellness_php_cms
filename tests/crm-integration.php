<?php
declare(strict_types=1);
use App\Services\{AgentRunTrace,AgentSessionService,CustomerCrmService,DatabaseService,ResourceService,SupportTicketService};

if (PHP_SAPI!=='cli'||!in_array(getenv('BAPX_MYSQL_HOST'),['localhost','127.0.0.1'],true)
    ||!preg_match('/(?:_test|_fixture)$/D',(string)getenv('BAPX_MYSQL_DB'))||getenv('BAPX_TEST_MODE')==='1') {
    fwrite(STDERR,"Use a disposable local test database.\n"); exit(2);
}
require __DIR__.'/../app/bootstrap.php';
$db=new DatabaseService(true);
$prefix=bin2hex(random_bytes(8)); $email='crm-'.$prefix.'@example.test';
$uid='qa-user-'.$prefix; $contact='qa-contact-'.$prefix; $therapy='qa-therapy-'.$prefix;
$sid=bin2hex(random_bytes(16)); $request=bin2hex(random_bytes(16));
$ticketId=substr(hash('sha256',$email.':'.$request),0,32);
$assert=static function(bool $ok,string $message):void { if (!$ok) throw new RuntimeException($message); };
try {
    $db->upsert('users',['id'=>$uid,'name'=>'QA synthetic','email'=>$email,'role'=>'customer','created_at'=>date('c')]);
    $db->upsert('contact_submissions',['id'=>$contact,'email'=>$email,'message'=>'Synthetic private contact body','status'=>'new','created_at'=>date('c')]);
    $db->upsert('therapy_sessions',['id'=>$therapy,'user_id'=>$uid,'status'=>'requested','created_at'=>date('c')]);
    $trace=new AgentRunTrace();
    $trace->provider('compatible','fixture',200,0,microtime(true),true);
    $trace->tool('published_practitioners','ok',microtime(true),'model','call_crm_fixture');
    $trace->provider('compatible','fixture',200,0,microtime(true),true);
    $trace->recordOutcome('model');
    $event=AgentSessionService::event('password=fixture-secret',['reply'=>'Synthetic published answer','response_mode'=>'model_with_retrieval'],200,$trace);
    $session=['id'=>$sid,'schema_version'=>1,'surface'=>'support','actor_type'=>'signed_in','customer_email'=>$email,'created_at'=>date('c'),'events'=>[$event]];
    $sessions=new AgentSessionService($db); $sessions->persist($session);
    $stored=$sessions->get($sid);
    $assert($stored!==null && count($stored['events'])===1,'Durable session readback');
    $assert(!str_contains(json_encode($stored),'fixture-secret'),'Credential redaction');
    $assert(in_array($sid,array_column($sessions->recent(),'id'),true),'Metadata query lists recent session');
    $tickets=new SupportTicketService($db);
    $t=$tickets->create($email,'Synthetic QA request','support chat',$request,$sid,AgentSessionService::handoffNote($session,'Synthetic QA request'));
    $assert($t['id']===$ticketId,'Ticket idempotency');
    $assert($tickets->create($email,'Synthetic QA request','support chat',$request,$sid)['id']===$ticketId,'Duplicate request returns same ticket');
    $old=ResourceService::revision($tickets->find($ticketId));
    $tickets->update($ticketId,$old,'note','Synthetic private staff note');
    $stale=false;
    try {$tickets->update($ticketId,$old,'reply','Stale answer');}catch(DomainException){$stale=true;}
    $assert($stale,'Stale writes rejected');
    $tickets->update($ticketId,ResourceService::revision($tickets->find($ticketId)),'reply','Synthetic staff answer');
    $visible=$tickets->track($email,$ticketId);
    $assert(($visible['reply']??'')==='Synthetic staff answer'&&!isset($visible['internal_notes']),'Only public ticket fields');
    $assert($tickets->track('different@example.test',$ticketId)===null,'Other customer denied');
    $timeline=(new CustomerCrmService($db))->timeline($email);
    foreach (['users','contact_submissions','therapy_sessions','support_tickets'] as $source)
        $assert(in_array($source,array_column($timeline,'source'),true),'Timeline contains '.$source);
    $assert(!str_contains(json_encode($timeline),'Synthetic private contact body'),'Timeline does not expose contact message');
    echo "PASS MariaDB CRM session archive, note revisions, ticket privacy and customer associations\n";
} finally {
    foreach (['agent_sessions'=>$sid,'support_tickets'=>$ticketId,'therapy_sessions'=>$therapy,'contact_submissions'=>$contact,'users'=>$uid] as $table=>$id) $db->delete($table,$id);
}
