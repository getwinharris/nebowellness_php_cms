<?php
declare(strict_types=1);
namespace App\Services;

/** Private, bounded session records. Never expose this collection through public tools. */
final class AgentSessionService {
    public const DAYS = 30;
    public const MAX_TURNS = 100;
    public const MAX_BYTES = 1048576;

    public function __construct(private DatabaseService $store = new DatabaseService(), private ?PrivateAgentJournalStore $privateStore=null) {}

    private function files(): PrivateAgentJournalStore {return $this->privateStore??=new PrivateAgentJournalStore();}

    public static function redact(string $text): string {
        // Best-effort credential minimisation; this is not a clinical data anonymizer.
        $text = preg_replace('/-----BEGIN [^-]*PRIVATE KEY-----[\s\S]*?-----END [^-]*PRIVATE KEY-----/i', '[redacted private key]', $text) ?? '';
        $text = preg_replace('/\b(?:Bearer\s+[^\s,;]+|sk-(?:proj-)?[A-Za-z0-9_-]{12,}|AIza[A-Za-z0-9_-]{25,})/i', '[redacted credential]', $text) ?? '';
        return preg_replace('/\b(password|passwd|api[_ -]?key|token|secret|otp|authorization)\s*[:=]\s*[^\s,;]+/i', '$1: [redacted]', $text) ?? '';
    }

    public static function event(string $question, array $response, int $status, AgentRunTrace $trace): array {
        $reply = $response['answer'] ?? $response['reply'] ?? $response['error'] ?? '';
        $draft = $response['draft'] ?? null;
        if (is_array($draft)) $reply = 'Draft response (' . (in_array($draft['mode'] ?? '', ['generated','manual','unavailable'], true) ? $draft['mode'] : 'other') . '). Editable draft contents are not archived.';
        return [
            'at'=>date('c'), 'question'=>self::redact($question),
            'reply'=>self::redact(is_string($reply) ? $reply : ''),
            'http_status'=>$status, 'outcome'=>$trace->outcome() ?? ($status >= 400 ? 'error' : 'unknown'),
            'response_mode'=>is_string($response['response_mode'] ?? null) ? $response['response_mode'] : '',
            'ticket_id'=>preg_match('/^[a-f0-9]{16,32}$/D',(string)($response['ticket_id']??'')) ? $response['ticket_id'] : null,
            'trace'=>$trace->metadata(),
        ];
    }

    public static function handoffNote(array $record, string $message): string {
        $events = $record['events'] ?? [];
        $last = end($events) ?: [];
        $tools = [];
        foreach ($events as $event) foreach ($event['trace']['steps'] ?? [] as $step) {
            if (($step['kind'] ?? '') === 'tool') $tools[] = $step['name'] . ' (' . $step['outcome'] . ')';
        }
        return "Customer requested clinic follow-up.\nRequest: " . self::redact($message)
            . "\nConversation turns recorded: " . count($events)
            . (!empty($record['omitted_turns']) ? " (some later turns exceeded archive limits)" : '')
            . (!empty($record['prior_history_unavailable']) ? " (earlier history was unavailable)" : '')
            . (!empty($last['reply']) ? "\nLast visible response: " . mb_substr((string)$last['reply'],0,800) : '')
            . "\nObserved tools: " . ($tools ? implode(', ',array_slice(array_unique($tools),0,12)) : 'None recorded')
            . "\nNext action: Review the request and respond through the support ticket. No appointment or resolution is confirmed.";
    }

    public function persist(array $record): void {
        if (!self::validId((string)($record['id'] ?? ''))) throw new \InvalidArgumentException('Invalid session.');
        if (($record['schema_version']??null)===PrivateAgentJournalStore::VERSION) {
            $this->files()->persist($record);
            return;
        }
        // Sessions started before the deployment retain their original archive
        // authority until reset/idle expiry. Never duplicate their record in files.
        if (($record['schema_version']??null)!==1) throw new \InvalidArgumentException('Unsupported session archive version.');
        $record['updated_at'] = date('c');
        $this->store->upsert('agent_sessions', $record);
    }

    public static function validId(string $id): bool { return (bool)preg_match('/^[a-f0-9]{32}$/D',$id); }

    public function get(string $id): ?array {
        if (!self::validId($id)) return null;
        $privateFailure=null;
        try {
            $row=$this->files()->readRecord($id);
            if($row!==null) return strtotime($row['updated_at'])>=time()-self::DAYS*86400?$row:null;
        } catch(\Throwable $error) {$privateFailure=$error;}
        // Historical remoteDB transcripts remain independently readable even
        // if the new directory is unavailable. An unknown ID is not reported
        // as absent when the private source could not be checked.
        $row=$this->store->find('agent_sessions',$id);
        if($row!==null&&($row['schema_version']??null)!==1)throw new \RuntimeException('Invalid legacy archive version.');
        if($row!==null&&strtotime((string)($row['updated_at']??''))>=time()-self::DAYS*86400)return $row;
        if($privateFailure!==null)throw new \RuntimeException('Private archive unavailable.',0,$privateFailure);
        return null;
    }

    /** Independent availability of the two archive sources; never pretend an outage is empty. */
    public function recentReport(): array {
        $items=[];$unavailable=[];
        try {
            foreach($this->files()->recent() as $row)$items[$row['id']]=$row;
        } catch(\Throwable) {$unavailable[]='private_files';}
        try {
            $legacy=$this->store->query("SELECT id, _updated_at AS updated_at, JSON_UNQUOTE(JSON_EXTRACT(_data,'$.surface')) AS surface,
                JSON_LENGTH(JSON_EXTRACT(_data,'$.events')) AS turns, JSON_UNQUOTE(JSON_EXTRACT(_data,'$.actor_type')) AS actor_type
                FROM agent_sessions WHERE _updated_at >= ? ORDER BY _updated_at DESC LIMIT 100", [date('Y-m-d H:i:s',time()-self::DAYS*86400)]);
            foreach($legacy as $row){
                if(!is_array($row)||!is_string($row['id']??null)||!self::validId($row['id'])) throw new \RuntimeException('Invalid legacy archive metadata.');
                if(isset($items[$row['id']]))continue;
                $items[$row['id']]=array_intersect_key($row,array_flip(['id','updated_at','surface','turns','actor_type']));
            }
        } catch(\Throwable) {$unavailable[]='legacy_remote';}
        usort($items,static fn(array $a,array $b):int=>(strtotime((string)($b['updated_at']??''))<=>strtotime((string)($a['updated_at']??'')))?:strcmp($a['id'],$b['id']));
        return ['items'=>array_slice($items,0,100),'unavailable'=>$unavailable];
    }

    public function recent(): array {
        $report=$this->recentReport();
        if($report['unavailable']!==[])throw new \RuntimeException('One or more archive sources unavailable.');
        return $report['items'];
    }

    /** New flat-file retention only. Historical remoteDB records are not deleted by this migration. */
    public function purgeExpired(): int {return $this->files()->purgeExpired();}
}
