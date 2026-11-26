<?php
declare(strict_types=1);

// The archival material is inert data. Never run a script or load a vendor runtime.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(1); }
const REA_SKILL_DIR = '.agents/REA';

function rsRequire(bool $condition,string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function rsRead(string $root,string $relative): string {
    rsRequire((bool)preg_match('#^[A-Za-z0-9._/-]+$#D',$relative)&&!str_starts_with($relative,'/')&&
        !in_array('..',explode('/',$relative),true),'Invalid evidence path.');
    $file=$root.'/'.$relative;
    rsRequire(realpath($file)===$file&&is_file($file)&&!is_link($file),'Missing or unsafe file: '.$relative);
    $data=file_get_contents($file);
    rsRequire(is_string($data),'Unable to read: '.$relative);
    return $data;
}
function rsJson(string $root,string $path): array {
    $data=json_decode(rsRead($root,$path),true,flags:JSON_THROW_ON_ERROR);
    rsRequire(is_array($data),'Invalid JSON: '.$path);
    return $data;
}
/** Enumerate only the archival or native subtree; refuse symlinks. */
function rsFiles(string $root,string $relative): array {
    $directory=$root.'/'.$relative;
    rsRequire(realpath($directory)===$directory&&is_dir($directory),'Missing or unsafe directory: '.$relative);
    $out=[];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory,FilesystemIterator::SKIP_DOTS)) as $entry) {
        rsRequire($entry->isFile()&&!$entry->isLink()&&realpath($entry->getPathname())===$entry->getPathname(),'Unsafe catalog entry.');
        $out[]=substr($entry->getPathname(),strlen($root)+1);
    }
    sort($out,SORT_STRING); return $out;
}
function rsValidate(string $root): array {
    $catalog=rsJson($root,REA_SKILL_DIR.'/catalog.json');
    $ledger=rsJson($root,'docs/research/rea/capabilities.json');
    $manifest=rsJson($root,'docs/research/rea/evidence/manifest.json');
    rsRequire(($catalog['schema_version']??null)===1&&is_array($catalog['sources']??null)&&is_array($catalog['skills']??null),'Invalid skill catalog.');
    $components=array_column($ledger['components'],null,'id');
    $sourceMap=array_column($manifest['sources'],null,'id');
    $refs=[];$originals=[];$archived=[];$vendors=[];
    foreach ($catalog['sources'] as $source) {
        $vendor=$source['id']??'';
        rsRequire(in_array($vendor,['wordpress','woocommerce'],true)&&!isset($vendors[$vendor]),'Invalid or duplicate vendor.');
        $vendors[$vendor]=$source;
        $repo=$vendor==='wordpress'?'WordPress/agent-skills':'woocommerce/agent-skills';
        rsRequire(($source['repository']??'')===$repo&&preg_match('/^[a-f0-9]{40}$/D',$source['revision']??'')&&
            preg_match('/^[a-f0-9]{40}$/D',$source['tree']??'')&&($source['license']??'')==='GPL-2.0-or-later','Missing pinned Git or licensing provenance.');
        rsRequire(($sourceMap[$vendor.'-agent-skills']['revision']??null)===$source['revision'],'Skill provenance is not pinned in the REA evidence manifest.');
        $prefix=REA_SKILL_DIR.'/reference/'.$vendor.'/';
        $license=$prefix.'LICENSE';
        rsRequire(($source['license_path']??'')===$license,'License is not preserved at the expected path.');
        $files=$source['files']??null;
        rsRequire(is_array($files)&&array_is_list($files)&&$files!==[],'Empty reference inventory.');
        $relativePaths=[];
        foreach ($files as $record) {
            $path=$record['path']??'';$copy=$record['copy']??'';
            rsRequire(is_string($path)&&$path!==''&&!isset($relativePaths[$path])&&
                !str_starts_with($path,'/')&&!in_array('..',explode('/',$path),true),'Duplicate or invalid original path.');
            $relativePaths[$path]=true;
            $extension=strtolower(pathinfo($path,PATHINFO_EXTENSION));
            $expected=$prefix.$path.(($path==='LICENSE'||in_array($extension,['md','json','txt'],true))?'':'.source.txt');
            rsRequire($copy===$expected&&!isset($archived[$copy]),'Reference copy path mismatch.');
            $data=rsRead($root,$copy);
            rsRequire(strlen($data)===($record['bytes']??null)&&hash('sha256',$data)===($record['sha256']??null),'Reference bytes changed: '.$copy);
            rsRequire((fileperms($root.'/'.$copy)&0111)===0,'Archived helper or document is executable: '.$copy);
            $archived[$copy]=true;
            if (preg_match('#^skills/([a-z0-9-]+)/SKILL\.md$#D',$path,$m)) {
                $originals[$copy]=['vendor'=>$vendor,'upstream'=>$m[1],'sha256'=>$record['sha256']];
            }
        }
        rsRequire(isset($archived[$license]),'Missing archived license.');
    }
    rsRequire(count($vendors)===2,'Both official skill collections are required.');
    $expectedFiles=array_keys($archived);sort($expectedFiles,SORT_STRING);
    rsRequire(rsFiles($root,REA_SKILL_DIR.'/reference')===$expectedFiles,'Reference inventory is incomplete or contains untracked files.');
    $skills=[];$seenOriginal=[];$nativePaths=[];
    foreach ($catalog['skills'] as $skill) {
        $id=$skill['native']??'';$original=$skill['original']??'';
        rsRequire(is_string($id)&&preg_match('/^rea-[a-z0-9-]+$/D',$id)&&!isset($skills[$id])&&isset($originals[$original])&&!isset($seenOriginal[$original]),'Duplicate or invalid adaptation mapping.');
        $upstream=$originals[$original];
        rsRequire($id==='rea-'.$upstream['upstream']&&($skill['upstream']??'')===$upstream['upstream']&&
            ($skill['vendor']??'')===$upstream['vendor']&&($skill['source_sha256']??'')===$upstream['sha256'],'Adaptation origin mismatch.');
        $path=REA_SKILL_DIR.'/skills/'.$id.'/SKILL.md';
        rsRequire(($skill['path']??'')===$path&&($skill['state']??'')==='native-reconstruction-guidance','Invalid native skill path or state.');
        $content=rsRead($root,$path);
        rsRequire(str_starts_with($content,"---\nname: ".$id."\n")&&str_contains($content,'## Native reconstruction contract')&&
            str_contains($content,'## Behavioral verification')&&str_contains($content,'## Out of scope')&&
            str_contains($content,$original)&&str_contains($content,$vendors[$skill['vendor']]['revision']),'Native skill missing engineering contract or provenance: '.$id);
        rsRequire(is_string($skill['description']??null)&&trim($skill['description'])!==''&&is_string($skill['exclude']??null)&&trim($skill['exclude'])!=='','Missing description or exclusions.');
        $ids=$skill['components']??null;
        rsRequire(is_array($ids)&&array_is_list($ids)&&$ids!==[]&&count($ids)===count(array_unique($ids)),'Missing or duplicate component IDs.');
        foreach ($ids as $cid) rsRequire(isset($components[$cid])&&str_contains($content,$cid),'Unknown or unmentioned capability: '.$cid);
        rsRequire(is_array($skill['evidence']??null)&&array_is_list($skill['evidence'])&&$skill['evidence']!==[],'No REA locator: '.$id);
        foreach ($skill['evidence'] as $ref) {
            $sid=$ref['source']??'';$locator=$ref['locator']??'';
            rsRequire(isset($sourceMap[$sid])&&is_string($locator)&&$locator!=='','Unknown source or locator: '.$id);
            if (!isset($refs[$sid])) {
                $entry=$sourceMap[$sid];
                rsRequire(is_string($entry['artifact']??null),'Source lacks archived graph: '.$sid);
                $artifact=rsRead($root,$entry['artifact']);
                rsRequire(hash('sha256',$artifact)===($entry['artifact_sha256']??''),'REA graph digest mismatch: '.$sid);
                $graph=json_decode($artifact,true,flags:JSON_THROW_ON_ERROR);
                $refs[$sid]=array_column($graph['entries']??$graph['files']??[],'path');
            }
            rsRequire(in_array($locator,$refs[$sid],true)&&str_contains($content,$locator),'Unresolved REA locator: '.$id.' / '.$sid.' / '.$locator);
        }
        $nativePaths[]=$path;$seenOriginal[$original]=true;$skills[$id]=$skill;
    }
    rsRequire(count($seenOriginal)===count($originals),'Every archived skill needs exactly one native adaptation.');
    sort($nativePaths,SORT_STRING);
    rsRequire(rsFiles($root,REA_SKILL_DIR.'/skills')===$nativePaths,'Native skill inventory differs from catalog.');
    ksort($skills,SORT_STRING);
    return [$skills,count($archived),$components];
}
try {
    $args=array_slice($argv,1);
    $root=dirname(__DIR__);
    if (count($args)>=2&&$args[count($args)-2]==='--root') {
        $root=array_pop($args);array_pop($args);
    }
    $root=realpath($root);rsRequire(is_string($root)&&is_dir($root),'Invalid root.');
    $mode=$args[0]??'list';
    rsRequire(in_array($mode,['check','list','show'],true)&&count($args)===($mode==='show'?2:1),'Usage: rea-skills.php check|list|show <native-id>');
    [$skills,$files,$components]=rsValidate($root);
    if ($mode==='show') {
        $name=$args[1];
        rsRequire(isset($skills[$name]),'Unknown native REA skill: '.$name);
        echo rsRead($root,$skills[$name]['path']);exit(0);
    }
    printf("REA skill library: %d native adaptations, %d pinned reference files.\n",count($skills),$files);
    if ($mode==='list') foreach ($skills as $id=>$s) {
        $states=array_map(static fn(string $cid):string=>$cid.':'.$components[$cid]['status'],$s['components']);
        printf("  %s — %s\n    %s\n",$id,$s['description'],implode(', ',$states));
    }
    else echo "Original bytes, licenses, one-to-one adaptations and REA locators verified.\n";
} catch (Throwable $e) {
    fwrite(STDERR,'REA skill validation failed: '.$e->getMessage().PHP_EOL);exit(1);
}
