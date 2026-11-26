<?php
declare(strict_types=1);
namespace App\Services;

/** Versioned, bounded transcript snapshots outside the public installation. */
final class PrivateAgentJournalStore {
    public const VERSION=2;
    private const MAX_RECORD_BYTES=1064960; // 1 MiB events plus bounded envelope.
    private const MAX_FILES=10000;
    private string $root;
    public function __construct(?string $directory=null,private ?\Closure $clock=null) {
        $app=realpath(app_path());
        if ($app===false) throw new \RuntimeException('Private journal location unavailable.');
        // An explicitly supplied directory is for isolated operator/test fixtures.
        // Never make the storage root configurable by a web request.
        if ($directory===null && getenv('BAPX_TEST_MODE')==='1') {
            $directory=PHP_SAPI==='cli'?(getenv('BAPX_AGENT_JOURNAL_TEST_DIR')?:null):null;
            if ($directory===null) throw new \RuntimeException('Private journal fixture directory required.');
        }
        $directory??=dirname($app).'/.bapxcms-journals-'.substr(hash('sha256',$app),0,24);
        $parent=realpath(dirname($directory));
        $name=basename($directory);
        if ($parent===false||$name===''||$name==='.'||$name==='..'||!str_starts_with($directory,DIRECTORY_SEPARATOR)) throw new \RuntimeException('Private journal location unavailable.');
        $this->root=$parent.DIRECTORY_SEPARATOR.$name;
        $documentRoot=realpath((string)($_SERVER['DOCUMENT_ROOT']??''));
        foreach ([$app,$documentRoot] as $web) {
            if (is_string($web)&&$web!==''&&($this->root===$web||str_starts_with($this->root,rtrim($web,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR))) {
                throw new \RuntimeException('Private journals must be outside the public web root.');
            }
        }
    }
    private function now(): int {return $this->clock===null?time():($this->clock)();}
    private function cutoff(): int {return $this->now()-AgentSessionService::DAYS*86400;}
    private function root(bool $create): bool {
        clearstatcache(true,$this->root);
        if (is_link($this->root)) throw new \RuntimeException('Private journal directory unsafe.');
        if (!file_exists($this->root)) {
            if (!$create) return false;
            if (!@mkdir($this->root,0700)&&!is_dir($this->root)) throw new \RuntimeException('Private journal directory unavailable.');
        }
        if (!is_dir($this->root)||!is_readable($this->root)||(fileperms($this->root)&0077)!==0) throw new \RuntimeException('Private journal directory permissions unsafe.');
        return true;
    }
    private function lock(bool $write,\Closure $action): mixed {
        if(!$this->root(false))throw new \RuntimeException('Private journal directory unavailable.');
        $file=$this->root.'/.journal.lock';
        if (is_link($file)) throw new \RuntimeException('Private journal lock unsafe.');
        $old=umask(0077);
        try {$handle=@fopen($file,'c+b');} finally {umask($old);}
        if ($handle===false) throw new \RuntimeException('Private journal lock unavailable.');
        try {
            if (is_link($file)||!is_file($file)||(fileperms($file)&0077)!==0) throw new \RuntimeException('Private journal lock permissions unsafe.');
            $mode=($write?LOCK_EX:LOCK_SH)|LOCK_NB;
            $locked=false;
            for($i=0;$i<100;$i++){if(flock($handle,$mode)){$locked=true;break;}usleep(20000);}
            if(!$locked)throw new \RuntimeException('Private journal is busy.');
            return $action();
        } finally {fclose($handle);}
    }
    private function path(string $id): string {
        if (!AgentSessionService::validId($id)) throw new \InvalidArgumentException('Invalid session identifier.');
        return $this->root.'/'.$id.'.json';
    }
    private static function stamp(mixed $value): string {
        if (!is_string($value)||strlen($value)>40||!preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(?:Z|[+-]\d\d:\d\d)$/D',$value)||strtotime($value)===false) {
            throw new \InvalidArgumentException('Invalid journal timestamp.');
        }
        return $value;
    }
    private static function integer(mixed $value): int {
        if(!is_int($value)||$value<0) throw new \InvalidArgumentException('Invalid journal count.');
        return $value;
    }
    private static function label(mixed $value,int $limit=100): string {
        if(!is_string($value)||strlen($value)>$limit||!preg_match('#^[a-zA-Z0-9._:/-]*$#D',$value)) throw new \InvalidArgumentException('Invalid journal label.');
        $safe=AgentSessionService::redact($value);return $safe===$value?$value:'redacted';
    }
    private static function event(array $event): array {
        $turn=$event['turn_id']??null;$trace=$event['trace']??null;
        if(!is_string($turn)||!AgentSessionService::validId($turn)||!is_array($trace)||!is_string($trace['trace_id']??null)||!preg_match('/^[a-f0-9]{16}$/D',$trace['trace_id'])) throw new \InvalidArgumentException('Invalid journal event identity.');
        $status=$event['http_status']??null;
        if(!is_int($status)||$status<100||$status>599||!is_string($event['question']??null)||!is_string($event['reply']??null)) throw new \InvalidArgumentException('Invalid journal event.');
        $steps=$trace['steps']??null;$sources=$trace['sources']??null;
        if(!is_array($steps)||!array_is_list($steps)||count($steps)>16||!is_array($sources)||!array_is_list($sources)||count($sources)>12) throw new \InvalidArgumentException('Invalid journal trace.');
        $projected=[];
        foreach($steps as $step){
            if(!is_array($step)||!in_array($step['kind']??null,['tool','provider'],true)) throw new \InvalidArgumentException('Invalid journal step.');
            $row=[];
            foreach(['kind','name','model','mode','outcome','origin','call_ref'] as $key) if(array_key_exists($key,$step)) $row[$key]=self::label($step[$key]);
            foreach(['status','duration_ms','input_tokens','output_tokens','total_tokens'] as $key) if(array_key_exists($key,$step)) $row[$key]=self::integer($step[$key]);
            $projected[]=$row;
        }
        $links=[];
        foreach($sources as $link) if(is_string($link)&&strlen($link)<=240&&preg_match('#^/[a-z0-9/_\#-]*$#D',$link)) $links[]=$link;
        $ticket=$event['ticket_id']??null;
        if($ticket!==null&&(!is_string($ticket)||!preg_match('/^[a-f0-9]{16,32}$/D',$ticket))) throw new \InvalidArgumentException('Invalid journal ticket link.');
        return ['at'=>self::stamp($event['at']??null),'turn_id'=>$turn,
            'question'=>mb_substr(AgentSessionService::redact($event['question']),0,8000),
            'reply'=>mb_substr(AgentSessionService::redact($event['reply']),0,8000),
            'http_status'=>$status,'outcome'=>self::label($event['outcome']??'unknown'),
            'response_mode'=>self::label($event['response_mode']??''),'ticket_id'=>$ticket,
            'trace'=>['trace_id'=>$trace['trace_id'],'steps'=>$projected,'omitted_steps'=>self::integer($trace['omitted_steps']??0),'sources'=>$links]];
    }
    private static function record(array $record): array {
        $id=$record['id']??null;$surface=$record['surface']??null;$actor=$record['actor_type']??null;$email=$record['customer_email']??'';
        if(!is_string($id)||!AgentSessionService::validId($id)||($record['schema_version']??null)!==self::VERSION||
            !in_array($surface,['admin','support'],true)||!in_array($actor,$surface==='admin'?['owner']:['guest','signed_in'],true)||
            !is_string($email)||($email!==''&&($actor!=='signed_in'||strlen($email)>254||!filter_var($email,FILTER_VALIDATE_EMAIL)))) throw new \InvalidArgumentException('Invalid journal owner.');
        $events=$record['events']??null;
        if(!is_array($events)||!array_is_list($events)||count($events)>AgentSessionService::MAX_TURNS) throw new \InvalidArgumentException('Invalid journal events.');
        $out=[];$seen=[];
        foreach($events as $event){
            if(!is_array($event))throw new \InvalidArgumentException('Invalid journal event.');
            $event=self::event($event);$key=self::eventKey($event);
            if(isset($seen[$key])) throw new \InvalidArgumentException('Duplicate journal event.');
            $seen[$key]=true;$out[]=$event;
        }
        if(!is_bool($record['prior_history_unavailable']??false)) throw new \InvalidArgumentException('Invalid journal history flag.');
        return ['id'=>$id,'schema_version'=>self::VERSION,'surface'=>$surface,'actor_type'=>$actor,'customer_email'=>$email,
            'created_at'=>self::stamp($record['created_at']??null),'events'=>$out,
            'omitted_turns'=>self::integer($record['omitted_turns']??0),'prior_history_unavailable'=>$record['prior_history_unavailable']??false,
            'scope'=>'Visible messages and observed tool/provider metadata only; credentials best-effort redacted. No private model reasoning, raw tool payloads or attachments.'];
    }
    private static function eventKey(array $event): string {return $event['turn_id'].':'.$event['trace']['trace_id'];}
    private function read(string $id): ?array {
        $file=$this->path($id);clearstatcache(true,$file);
        if(is_link($file))throw new \RuntimeException('Private journal file unsafe.');
        if(!file_exists($file))return null;
        if(!is_file($file)||!is_readable($file)||(fileperms($file)&0077)!==0||filesize($file)>self::MAX_RECORD_BYTES)throw new \RuntimeException('Private journal file unavailable.');
        $bytes=@file_get_contents($file,false,null,0,self::MAX_RECORD_BYTES+1);
        if(!is_string($bytes)||strlen($bytes)>self::MAX_RECORD_BYTES)throw new \RuntimeException('Private journal read failed.');
        $raw=json_decode($bytes,true,flags:JSON_THROW_ON_ERROR);
        if(!is_array($raw)||($raw['id']??null)!==$id)throw new \RuntimeException('Private journal identity mismatch.');
        return self::record($raw)+['updated_at'=>self::stamp($raw['updated_at']??null)];
    }
    private function write(array $record): void {
        $bytes=json_encode($record,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        if(strlen($bytes)>self::MAX_RECORD_BYTES)throw new \RuntimeException('Private journal exceeds archive limit.');
        $path=$this->path($record['id']);
        if(is_link($path))throw new \RuntimeException('Private journal file unsafe.');
        $old=umask(0077);
        try {$tmp=tempnam($this->root,'.journal-');}finally{umask($old);}
        if($tmp===false)throw new \RuntimeException('Private journal staging unavailable.');
        try {
            if(dirname($tmp)!==$this->root||!chmod($tmp,0600))throw new \RuntimeException('Private journal staging permissions unsafe.');
            $handle=fopen($tmp,'wb');
            if($handle===false)throw new \RuntimeException('Private journal staging failed.');
            try {
                if(fwrite($handle,$bytes)!==strlen($bytes)||!fflush($handle)||(function_exists('fsync')&&!fsync($handle)))throw new \RuntimeException('Private journal write failed.');
            } finally {fclose($handle);}
            if(!rename($tmp,$path))throw new \RuntimeException('Private journal commit failed.');
        }finally{if(is_file($tmp))@unlink($tmp);}
    }
    public function persist(array $snapshot): void {
        $next=self::record($snapshot);
        $this->root(true);
        $this->lock(true,function()use($next):void{
            $prior=$this->read($next['id']);
            if($prior!==null){
                if(strtotime($prior['updated_at'])<$this->cutoff())throw new \DomainException('This archived session expired.');
                foreach(['surface','actor_type','customer_email','created_at'] as $key) if($prior[$key]!==$next[$key])throw new \DomainException('Journal owner or session identity changed.');
                $events=[];$tickets=[];
                foreach($prior['events'] as $event){$events[self::eventKey($event)]=$event;if($event['http_status']<400&&$event['ticket_id']!==null)$tickets[$event['ticket_id']]=true;}
                foreach($next['events'] as $event){
                    $key=self::eventKey($event);
                    if(isset($events[$key])){
                        if($events[$key]!==$event)throw new \DomainException('Archived journal event changed.');
                        continue;
                    }
                    if($event['http_status']<400&&$event['ticket_id']!==null&&isset($tickets[$event['ticket_id']]))continue;
                    $events[$key]=$event;
                    if($event['http_status']<400&&$event['ticket_id']!==null)$tickets[$event['ticket_id']]=true;
                }
                if(count($events)>AgentSessionService::MAX_TURNS)throw new \RuntimeException('Private journal exceeds archive limit.');
                $next['events']=array_values($events);
                $next['omitted_turns']=max($next['omitted_turns'],$prior['omitted_turns']);
                $next['prior_history_unavailable']=$next['prior_history_unavailable']||$prior['prior_history_unavailable'];
                $old=$prior;unset($old['updated_at']);
                if($next===$old)return; // Replay cannot extend retention or overwrite an event.
            }
            if($prior===null&&count($this->files())>=self::MAX_FILES)throw new \RuntimeException('Private journal inventory is full. Review expired records.');
            if($prior===null&&strtotime($next['created_at'])<$this->cutoff()){
                $recent=false;foreach($next['events'] as $event)if(strtotime($event['at'])>=$this->cutoff()){$recent=true;break;}
                if(!$recent)throw new \DomainException('This archived session expired.');
            }
            $next['updated_at']=date('c',$this->now());
            $this->write($next);
        });
    }
    /** Includes expired records internally so callers cannot revive an expired file via a fallback. */
    public function readRecord(string $id): ?array {
        if(!AgentSessionService::validId($id)||!$this->root(false))return null;
        return $this->lock(false,fn()=> $this->read($id));
    }
    private function files(): array {
        $items=[];
        foreach(new \DirectoryIterator($this->root) as $entry){
            $name=$entry->getFilename();
            if(!preg_match('/^([a-f0-9]{32})\.json$/D',$name,$match))continue;
            if($entry->isLink()||!$entry->isFile())throw new \RuntimeException('Private journal inventory unsafe.');
            $items[$match[1]]=$entry->getMTime();
            if(count($items)>self::MAX_FILES)throw new \RuntimeException('Private journal inventory exceeds review limit.');
        }
        arsort($items,SORT_NUMERIC);return $items;
    }
    public function recent(): array {
        if(!$this->root(false))return [];
        return $this->lock(false,function():array{
            $out=[];
            foreach($this->files() as $id=>$mtime){
                if($mtime<$this->cutoff())continue;
                $record=$this->read((string)$id);
                if($record===null||strtotime($record['updated_at'])<$this->cutoff())continue;
                $out[]=['id'=>$record['id'],'updated_at'=>$record['updated_at'],'surface'=>$record['surface'],'actor_type'=>$record['actor_type'],'turns'=>count($record['events'])];
                if(count($out)>=100)break;
            }
            return $out;
        });
    }
    /** Explicit owner cleanup only; never touches legacy remoteDB records. */
    public function purgeExpired(): int {
        if(!$this->root(false))return 0;
        return $this->lock(true,function():int{
            $count=0;
            foreach(array_reverse($this->files(),true) as $id=>$mtime){
                if($mtime>=$this->cutoff())continue;
                $record=$this->read((string)$id);
                if($record!==null&&strtotime($record['updated_at'])<$this->cutoff()){
                    if(!unlink($this->path((string)$id)))throw new \RuntimeException('Private journal cleanup failed.');
                    if(++$count>=100)break;
                }
            }
            return $count;
        });
    }
}
