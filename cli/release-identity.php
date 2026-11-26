<?php
declare(strict_types=1);

// This is a developer tool, not a second public endpoint.
if (PHP_SAPI!=='cli') { http_response_code(404); exit(1); }
require dirname(__DIR__).'/app/Services/ReleaseIdentityService.php';

use App\Services\ReleaseIdentityService;

function output(array $data,int $exitCode): never {
    echo json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
    exit($exitCode);
}

[$status,$local]=(new ReleaseIdentityService())->inspect();
if ($status!==200) output(['state'=>'local_unavailable','local'=>$local],1);
$command=$argv[1]??'status';
if ($command==='status'&&count($argv)<=2) output($local,0);
if ($command!=='verify'||count($argv)!==3) {
    fwrite(STDERR,"Usage: release-identity.php status | verify https://deployment.example\n");
    exit(2);
}
$origin=rtrim($argv[2],'/');
$parts=parse_url($origin);
if (!is_array($parts)||empty($parts['host'])||isset($parts['user'])||isset($parts['pass'])||isset($parts['query'])||isset($parts['fragment'])||
    !empty($parts['path'])||!in_array($parts['scheme']??'',['https','http'],true)||
    (($parts['scheme']??'')==='http'&&!in_array($parts['host'],['127.0.0.1','localhost','[::1]'],true))) {
    fwrite(STDERR,"Provide an HTTPS deployment origin (HTTP is allowed only for local fixtures).\n");
    exit(2);
}
$url=$origin.'/is-sha-valid?'.http_build_query(['fingerprint'=>$local['fingerprint']]);
$ch=curl_init($url);
$body='';
curl_setopt_array($ch,[
    CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>12,CURLOPT_CONNECTTIMEOUT=>5,
    CURLOPT_HTTPHEADER=>['Accept: application/json','Cache-Control: no-cache'],
    CURLOPT_WRITEFUNCTION=>static function($ch,string $chunk) use (&$body):int {
        if (strlen($body)+strlen($chunk)>16384) return 0;
        $body.=$chunk;return strlen($chunk);
    },
]);
$ok=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);unset($ch);
$remote=json_decode($body,true);
if ($ok===false||!is_array($remote)||($remote['scope']??null)!==ReleaseIdentityService::SCOPE) {
    output(['state'=>'remote_unavailable','http'=>$http,'local'=>$local],1);
}
$fingerprint=$remote['fingerprint']??null;
$remoteSha=$remote['revision']??null;
if (!is_string($fingerprint)||!preg_match('/^[0-9a-f]{64}$/D',$fingerprint)||
    ($remoteSha!==null&&(!is_string($remoteSha)||!preg_match('/^[0-9a-f]{40}$/D',$remoteSha)))) {
    output(['state'=>'remote_unavailable','http'=>$http,'local'=>$local],1);
}
$same=hash_equals($local['fingerprint'],$fingerprint);
$revisionMatch=$local['revision']!==null&&$remoteSha!==null?hash_equals($local['revision'],$remoteSha):null;
$match=$http===200&&$same&&($remote['fingerprint_match']??null)===true&&($remote['state']??null)==='matched'&&$revisionMatch!==false;
output([
    'state'=>$match?($revisionMatch===true?'source_and_revision_matched':'source_matched_revision_unknown'):($http===409||!$same||$revisionMatch===false?'mismatch':'remote_unavailable'),
    'http'=>$http,'scope'=>ReleaseIdentityService::SCOPE,
    'local_revision'=>$local['revision'],'remote_revision'=>$remoteSha,'revision_match'=>$revisionMatch,
    'local_fingerprint'=>$local['fingerprint'],'remote_fingerprint'=>$fingerprint,'fingerprint_match'=>$same,
],$match?0:1);
