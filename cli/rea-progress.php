<?php
declare(strict_types=1);

// Developer-only, local repository metadata. No application bootstrap or database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(1); }
const REA_DIR = 'docs/research/rea';
const REA_REGISTER = REA_DIR . '/capabilities.json';
const REA_REPORT = REA_DIR . '/progress.md';

function reaRequire(bool $ok,string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
function reaText(mixed $v): bool { return is_string($v) && trim($v)!==''; }
function reaList(mixed $v): bool { return is_array($v)&&array_is_list($v); }
function reaRead(string $root,string $path): string {
    reaRequire((bool)preg_match('#^[A-Za-z0-9._/-]+$#D',$path)
        && !str_starts_with($path,'/') && !in_array('..',explode('/',$path),true),'Invalid repository evidence path.');
    $full=realpath($root.'/'.$path);
    reaRequire(is_string($full)&&str_starts_with($full,$root.DIRECTORY_SEPARATOR)&&is_file($full),"Missing repository evidence: $path");
    $text=file_get_contents($full);
    reaRequire(is_string($text),"Cannot read repository evidence: $path");
    return $text;
}
function reaJson(string $root,string $path): array {
    $data=json_decode(reaRead($root,$path),true,flags:JSON_THROW_ON_ERROR);
    reaRequire(is_array($data),"Invalid JSON record: $path");
    return $data;
}
function reaValidate(string $root): array {
    $register=reaJson($root,REA_REGISTER);
    $manifest=reaJson($root,REA_DIR.'/evidence/manifest.json');
    reaRequire(($register['schema_version']??null)===1 && is_int($register['scope_revision']??null) && $register['scope_revision']>0,'Invalid scope/schema version.');
    reaRequire((bool)preg_match('/^[0-9a-f]{40}$/D',$register['baseline_commit']??''),'Baseline must identify an exact source commit.');
    reaRequire(reaList($register['tracks']??null)&&reaList($register['components']??null)&&reaList($register['excluded']??null)&&reaList($register['updates']??null),'Missing register collections.');
    reaRequire($register['components']!==[] && $register['tracks']!==[] && $register['updates']!==[],'Register cannot be empty.');
    reaRequire(($manifest['schema_version']??null)===1 && reaList($manifest['sources']??null),'Invalid reference manifest.');
    $sources=[]; $referencePaths=[];
    foreach ($manifest['sources'] as $s) {
        $id=$s['id']??'';
        reaRequire(is_string($id)&&preg_match('/^[a-z0-9-]+$/D',$id)&&!isset($sources[$id]),'Duplicate or invalid reference source.');
        foreach (['label','url','revision','scope'] as $k) reaRequire(reaText($s[$k]??null),"Incomplete reference $id.");
        reaRequire(str_starts_with($s['url'],'https://'),"Reference $id needs HTTPS provenance.");
        if (isset($s['artifact'])) {
            reaRequire(str_starts_with($s['artifact'],REA_DIR.'/evidence/'),"Invalid reference artifact for $id.");
            $contents=reaRead($root,$s['artifact']);
            reaRequire(hash_equals(hash('sha256',$contents),(string)($s['artifact_sha256']??'')),"Reference digest mismatch: $id.");
            if (str_ends_with($s['artifact'],'.json')) {
                $artifact=json_decode($contents,true,flags:JSON_THROW_ON_ERROR);
                $items=$artifact['entries']??$artifact['files']??null;
                if (reaList($items)) {
                    $referencePaths[$id]=array_values(array_filter(array_column($items,'path'),'is_string'));
                }
            }
        }
        $sources[$id]=$s;
    }
    $tracks=[];
    foreach ($register['tracks'] as $track) {
        $id=$track['id']??'';
        reaRequire(is_string($id)&&preg_match('/^[a-z][a-z0-9]*$/D',$id)&&!isset($tracks[$id]),'Duplicate or invalid track.');
        reaRequire(reaText($track['label']??null)&&reaText($track['scope']??null),"Incomplete track $id.");
        $tracks[$id]=['track'=>$track,'rows'=>[]];
    }
    $rows=[];
    foreach ($register['components'] as $row) {
        $id=$row['id']??'';$track=$row['track']??'';
        reaRequire(is_string($id)&&isset($tracks[$track])&&preg_match('/^'.preg_quote($track,'/').'-[0-9]{2,}$/D',$id)&&!isset($rows[$id]),'Duplicate or invalid component ID.');
        foreach (['title','acceptance','finding'] as $k) reaRequire(reaText($row[$k]??null),"Incomplete component $id.");
        reaRequire(in_array($row['status']??null,['implemented','partial','missing'],true),"Invalid status: $id.");
        foreach (['issues','references','source','tests'] as $k) reaRequire(reaList($row[$k]??null),"Invalid $k: $id.");
        reaRequire($row['references']!==[] && $row['issues']!==[],"Component $id needs research and issue links.");
        foreach ($row['issues'] as $n) reaRequire(is_int($n)&&$n>0,"Invalid issue: $id.");
        foreach ($row['references'] as $ref) {
            $refId=$ref['source']??''; $locator=$ref['locator']??null;
            reaRequire(isset($sources[$refId])&&reaText($locator),"Unresolved research locator: $id.");
            if (isset($referencePaths[$refId])) {
                reaRequire(in_array($locator,$referencePaths[$refId],true),"Reference locator missing from selected evidence: $id / $refId.");
            }
        }
        if ($row['status']==='implemented') reaRequire($row['source']!==[]&&$row['tests']!==[],"Implemented $id lacks source/test evidence.");
        if ($row['status']==='partial') reaRequire($row['source']!==[],"Partial $id lacks observed implementation.");
        foreach (array_merge($row['source'],$row['tests']) as $ref) {
            reaRequire(reaText($ref['path']??null)&&reaText($ref['anchor']??null),"Missing code/test locator: $id.");
            reaRequire(str_contains(reaRead($root,$ref['path']),$ref['anchor']),"Evidence anchor missing: $id / ".$ref['path']);
        }
        $production=$row['production']??null;
        if ($production!==null) {
            reaRequire($row['status']==='implemented'&&is_array($production),"Production evidence requires implemented component: $id.");
            foreach (['observed_at','release','evidence','scenario'] as $k) reaRequire(reaText($production[$k]??null),"Incomplete production observation: $id.");
            reaRequire((bool)preg_match('/^[0-9a-f]{7,40}$/D',$production['release'])&&str_starts_with($production['evidence'],'https://github.com/'),"Invalid production provenance: $id.");
        }
        $rows[$id]=$row;$tracks[$track]['rows'][]=$row;
    }
    foreach ($tracks as $id=>$track) reaRequire($track['rows']!==[],"Empty track: $id.");
    foreach ($register['excluded'] as $exclusion) reaRequire(reaText($exclusion),'Empty exclusion.');
    $updates=[];
    foreach ($register['updates'] as $u) {
        $id=$u['id']??'';
        reaRequire(is_string($id)&&preg_match('/^[a-zA-Z0-9._-]+$/D',$id)&&!isset($updates[$id])&&reaText($u['summary']??null)&&reaText($u['date']??null),'Invalid or duplicate scope review.');
        reaRequire(reaList($u['issues']??null)&&$u['issues']!==[]&&reaList($u['components']??null),"Invalid scope review $id.");
        foreach ($u['issues'] as $n) reaRequire(is_int($n)&&$n>0,"Invalid scope review issue: $id.");
        // Historic updates can reference retired IDs after an explicit scope revision.
        foreach ($u['components'] as $c) reaRequire(is_string($c)&&preg_match('/^[a-z][a-z0-9]*-[0-9]{2,}$/D',$c),"Invalid scope review component: $id.");
        $updates[$id]=$u;
    }
    return [$register,$tracks,$sources];
}
function reaCell(string $v): string { return str_replace(["|","\r","\n"],['\|',' ',' '],$v); }
function reaCounts(array $rows): array {
    $n=count($rows);$done=$partial=$missing=$observed=0;
    foreach ($rows as $r) {
        match ($r['status']) {'implemented'=>$done++,'partial'=>$partial++,'missing'=>$missing++};
        if (($r['production']??null)!==null) $observed++;
    }
    return [$n,$done,$partial,$missing,$observed];
}
function reaPercent(int $n,int $d): string {return number_format($d ? 100*$n/$d : 0,1).'%';}
function reaSummaryRow(string $label,array $rows): string {
    [$n,$done,$partial,$missing,$observed]=reaCounts($rows);
    return '| '.reaCell($label)." | $done / $n | ".reaPercent($done,$n)." | $partial | $missing | $observed / $n | ".reaPercent($observed,$n)." |";
}
function reaReport(array $register,array $tracks,array $sources): string {
    $lines=[
        '---','type: doc','title: REA capability progress (generated)','description: Evidence-based scope and progress across native CMS, agents, MCP, A2UI and integrations.','category: docs','---','',
        '# REA capability progress','',
        'Generated from [capabilities.json](capabilities.json) by `./bapXphp rea update`. Do not edit this file manually.',
        'Scope revision: '.$register['scope_revision'].'. Initial source baseline: `'.$register['baseline_commit'].'` ('.$register['baseline_date'].').',
        'Read-only bapX reference baseline: `'.$register['reference_backend'].'`. Historical reference evidence is not Nebo acceptance.','',
        'Implementation coverage counts only complete scoped components with source and test anchors. Partial and missing count zero. Every component is weighted equally; this is not upstream product parity or an effort estimate. Production observations are separately recorded historical scenarios, not certification of the current deployed Git SHA or every acceptance case.','',
        '| Track | Implemented | Coverage | Partial | Missing | Live observed | Observation coverage |','|---|---:|---:|---:|---:|---:|---:|'
    ];
    foreach ($tracks as $track) $lines[]=reaSummaryRow($track['track']['label'],$track['rows']);
    $lines[]=reaSummaryRow('**Total**',$register['components']);
    $lines[]='';$lines[]='## Explicit exclusions';$lines[]='';
    foreach ($register['excluded'] as $x) $lines[]='- '.$x;
    foreach ($tracks as $track) {
        $lines[]='';$lines[]='## '.$track['track']['label'];$lines[]='';$lines[]=$track['track']['scope'];$lines[]='';
        $lines[]='| ID | Capability | Status | Acceptance / remaining work | Issues |';
        $lines[]='|---|---|---|---|---|';
        foreach ($track['rows'] as $r) {
            $issues=implode(', ',array_map(fn(int $n):string=>'[#'.$n.'](https://github.com/getwinharris/nebowellness_php_cms/issues/'.$n.')',$r['issues']));
            $lines[]='| '.$r['id'].' | '.reaCell($r['title']).' | '.$r['status'].' | '.reaCell($r['acceptance'].' — '.$r['finding']).' | '.$issues.' |';
        }
    }
    $lines[]='';$lines[]='## Recorded production observations';$lines[]='';
    foreach ($register['components'] as $r) if (($p=$r['production']??null)!==null) {
        $lines[]='- **'.$r['id'].'** — '.$p['scenario'].' Observed '.$p['observed_at'].', reported release `'.$p['release'].'`. [Evidence]('.$p['evidence'].').';
    }
    $lines[]='';$lines[]='## Reference sources';$lines[]='';
    foreach ($sources as $s) {
        $artifact=isset($s['artifact'])?' · [archived evidence](evidence/'.basename($s['artifact']).')':'';
        $lines[]='- **'.$s['id'].'** — ['.$s['label'].']('.$s['url'].') · `'.$s['revision'].'`'.$artifact.'. '.$s['scope'];
    }
    $lines[]='';$lines[]='## Scope reviews and updates';$lines[]='';
    foreach (array_reverse($register['updates']) as $u) $lines[]='- '.$u['date'].' · `'.$u['id'].'` — '.$u['summary'];
    return implode("\n",$lines)."\n";
}
function reaGit(string $root,array $args,bool $allowFailure=false): ?string {
    $proc=proc_open(array_merge(['git','-C',$root],$args),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
    reaRequire(is_resource($proc),'Cannot execute local Git.');
    fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    $code=proc_close($proc);
    if ($code!==0&&$allowFailure) return null;
    reaRequire($code===0,'Could not inspect Git change scope.');
    return (string)$out;
}
function reaImpact(string $root,string $base,array $register): void {
    reaRequire((bool)preg_match('/^[0-9a-f]{40}$/D',$base),'Impact check requires exact base SHA.');
    // In pull_request jobs the checkout is the merge ref, which also includes the PR.
    reaGit($root,['cat-file','-e',$base.'^{commit}']);
    $paths=explode("\0",reaGit($root,['diff','--name-only','-z',$base.'...HEAD'])??'');
    $runtime=array_filter($paths,fn(string $p):bool=>(bool)preg_match('#^(?:(?:app|api|assets|views|config|integrations|storage/schema|content/blog/posts)/|index\.php$|\.htaccess$)#',$p)
        && !preg_match('#^assets/(?:images|fonts)/#',$p));
    $before=reaGit($root,['show',$base.':'.REA_REGISTER],true);
    if ($before===null) { echo "REA initial register: no prior scope to compare.\n";return; }
    $old=json_decode($before,true,flags:JSON_THROW_ON_ERROR);
    $oldIds=array_column($old['components'],'id');$newIds=array_column($register['components'],'id');sort($oldIds);sort($newIds);
    $scopeChanged=$oldIds!==$newIds||$old['excluded']!==$register['excluded']||$old['tracks']!==$register['tracks'];
    reaRequire($register['scope_revision'] >= $old['scope_revision'],'Scope revision cannot decrease.');
    $oldUpdates=array_column($old['updates'],'id');
    // Prior review evidence is append-only. New entries explain any correction.
    reaRequire(array_slice($register['updates'],0,count($old['updates']))===$old['updates'],'Prior REA review entries must remain unchanged.');
    $newUpdates=array_values(array_filter($register['updates'],fn(array $u):bool=>!in_array($u['id'],$oldUpdates,true)));
    if ($scopeChanged) {
        reaRequire($register['scope_revision']>$old['scope_revision'],'Change to denominator/exclusions requires a higher scope_revision.');
        reaRequire(count(array_filter($newUpdates,fn(array $u):bool=>reaText($u['scope_change']??null)))>0,'Scope change requires documented scope_change rationale.');
    }
    if ($runtime!==[] || $scopeChanged || $old['components']!==$register['components']) {
        reaRequire($newUpdates!==[],'Runtime/coverage change requires a new REA review entry in capabilities.json.');
        $affected=[];
        $previousRows=array_column($old['components'],null,'id');
        foreach ($register['components'] as $row) if (($previousRows[$row['id']]??null)!==$row) $affected[]=$row['id'];
        $reviewed=array_merge(...array_column($newUpdates,'components'));
        foreach ($affected as $id) reaRequire(in_array($id,$reviewed,true),"Changed capability $id is not referenced by the new REA review.");
    }
    echo "REA change impact accepted (".count($runtime)." runtime files).\n";
}
try {
    $mode=$argv[1]??'status';$root=dirname(__DIR__);$args=array_slice($argv,2);
    if (($args[0]??null)==='--root') {$root=$args[1]??'';$args=array_slice($args,2);}
    $root=realpath($root);
    reaRequire(is_string($root)&&is_dir($root),'Invalid repository root.');
    [$register,$tracks,$sources]=reaValidate($root);
    $report=reaReport($register,$tracks,$sources);
    if ($mode==='status') { foreach ($tracks as $track) echo reaSummaryRow($track['track']['label'],$track['rows'])."\n";echo reaSummaryRow('Total',$register['components'])."\n"; }
    elseif ($mode==='update') {reaRequire(file_put_contents($root.'/'.REA_REPORT,$report)!==false,'Could not write REA progress.');echo "REA progress updated.\n";}
    elseif ($mode==='check') {reaRequire(reaRead($root,REA_REPORT)===$report,'REA report stale. Run ./bapXphp update.');echo "REA register, evidence anchors, digests and report valid.\n";}
    elseif ($mode==='impact') {reaImpact($root,$args[0]??'',$register);}
    else throw new RuntimeException('Usage: rea-progress.php status|update|check|impact BASE_SHA');
} catch (Throwable $error) {
    fwrite(STDERR,'REA validation failed: '.$error->getMessage()."\n");exit(1);
}
