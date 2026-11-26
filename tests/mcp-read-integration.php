<?php
declare(strict_types=1);
use App\Services\{DatabaseService,McpGrantService,SettingsService,ConsultationFeeService,AbilitySchema,McpReadService,RateLimiter};
if(PHP_SAPI!=='cli'||!in_array(getenv('BAPX_MYSQL_HOST'),['localhost','127.0.0.1'],true)||!preg_match('/(?:_test|_fixture)$/D',(string)getenv('BAPX_MYSQL_DB'))||getenv('BAPX_TEST_MODE')==='1'){fwrite(STDERR,"Disposable local database required.\n");exit(2);}
require __DIR__.'/../app/bootstrap.php';
$store=new DatabaseService(true);
$old=$store->find('settings',SettingsService::SETTINGS_ID);
$ids=[];$server=null;$pipes=[];
$assert=static function(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);};
$port=18000+random_int(0,14000);$base='http://127.0.0.1:'.$port;
$headers=['Accept: application/json, text/event-stream','Content-Type: application/json','MCP-Protocol-Version: '.McpReadService::VERSION];
$meta=['io.modelcontextprotocol/protocolVersion'=>McpReadService::VERSION,'io.modelcontextprotocol/clientInfo'=>['name'=>'Nebo fixture','version'=>'1'],'io.modelcontextprotocol/clientCapabilities'=>(object)[]];
try{
    $store->upsert('settings',['id'=>SettingsService::SETTINGS_ID,'module_shop'=>'0','module_campaigns'=>'0','module_blog'=>'0','consultation_fees'=>[
        ConsultationFeeService::normalize(['title'=>'MCP public fixture','status'=>'published','currency'=>'INR','amount'=>'123.45','min_minutes'=>'30','max_minutes'=>'30'],'mcp-fixture'),
        ConsultationFeeService::normalize(['title'=>'Private draft fixture','status'=>'draft','currency'=>'INR','amount'=>'999.99','min_minutes'=>'30','max_minutes'=>'30'],'mcp-draft')
    ],'consultation_inclusions'=>[]]);
    $grants=new McpGrantService($store);
    $grant=$grants->issue('MCP HTTP fixture',1);$ids[]=$grant['id'];
    $token=$grant['token'];
    $record=$store->find('mcp_grants',$grant['id']);
    $assert($record['token_digest']===hash('sha256',$token)&&!str_contains(json_encode($record),$token),'Only token digest stored');
    $assert(!str_contains(json_encode($grants->listing()),'token_digest'),'Listing omits digest');
    $env=array_merge(getenv()?:[],['APP_URL'=>$base]);
    $server=proc_open([PHP_BINARY,'-S','127.0.0.1:'.$port,'index.php'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,app_path(),$env);
    if(!is_resource($server))throw new RuntimeException('Unable to start isolated MCP HTTP server');
    for($i=0;$i<80;$i++){
        $socket=@fsockopen('127.0.0.1',$port,$errno,$errstr,0.1);
        if($socket){fclose($socket);break;}
        usleep(50000);
    }
    $call=static function(string $method,array $params=[],?string $credential=null,array $extra=[],?string $body=null,string $verb='POST')use($base,$headers,$meta):array{
        $payload=$body??json_encode(['jsonrpc'=>'2.0','id'=>'fixture-1','method'=>$method,'params'=>['_meta'=>$meta]+$params],JSON_THROW_ON_ERROR);
        $h=array_merge($headers,['Mcp-Method: '.$method],isset($params['name'])?['Mcp-Name: '.$params['name']]:[],$credential===null?[]:['Authorization: Bearer '.$credential],$extra);
        $ch=curl_init($base.'/mcp');
        curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>$verb,CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>$h,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_TIMEOUT=>5]);
        $raw=curl_exec($ch);$http=curl_getinfo($ch,CURLINFO_HTTP_CODE);$size=curl_getinfo($ch,CURLINFO_HEADER_SIZE);curl_close($ch);
        if(!is_string($raw))throw new RuntimeException('MCP HTTP request failed');
        return [$http,json_decode(substr($raw,$size),true),substr($raw,0,$size)];
    };
    [$http,$body]=$call('tools/list');$assert($http===401,'Missing grant rejected');
    [$http,$body]=$call('tools/list',[],null,['Cookie: PHPSESSID=forged-owner-session']);$assert($http===401,'Browser cookie does not authorize MCP');
    [$http,$body]=$call('tools/list',[],$token,['Origin: https://not-nebo.example']);$assert($http===403,'Foreign origin denied');
    [$http,$body]=$call('server/discover',[],$token);
    $assert($http===200&&$body['result']['supportedVersions']===[McpReadService::VERSION],'Pinned discovery');
    [$http,$body,$responseHeaders]=$call('tools/list',[],$token);
    $names=array_column($body['result']['tools']??[],'name');
    $assert($http===200&&$names===['published_consultation_fees','published_practitioners','search_clinic_knowledge'],'Exact module-filtered public list: no session or owner access');
    $assert(str_contains(strtolower($responseHeaders),'no-store')&&!str_contains(strtolower($responseHeaders),'set-cookie:'),'Non-cacheable stateless transport');
    $tools=array_column($body['result']['tools'],null,'name');
    [$http,$body]=$call('tools/call',['name'=>'published_consultation_fees','arguments'=>(object)[]],$token);
    $data=$body['result']['structuredContent']??[];
    $assert($http===200&&($body['result']['isError']??true)===false&&count($data['fees']??[])===1,'Real typed published read only');
    $assert($data['fees'][0]['amount_minor']===12345&&$data['fees'][0]['title']==='MCP public fixture','Exact published fee');
    $assert(AbilitySchema::valid($data,$tools['published_consultation_fees']['outputSchema']),'Returned data conforms to advertised schema');
    foreach(['clinic_context','owner_dashboard_summary','published_programmes','find_order'] as $name){
        [$http,$body]=$call('tools/call',['name'=>$name,'arguments'=>(object)[]],$token);
        $assert($http===400&&($body['error']['code']??null)===-32602,'Non-granted tool rejected');
    }
    [$http,$body]=$call('tools/call',['name'=>'published_consultation_fees','arguments'=>['email'=>'foreign@example.test']],$token);
    $assert($http===200&&($body['result']['isError']??false)&&!isset($body['result']['structuredContent']),'Invalid arguments no private output');
    [$http,$body]=$call('tools/call',['name'=>'published_consultation_fees','arguments'=>[]],$token);
    $assert($http===400,'Array arguments not accepted as an object');
    [$http,$body]=$call('tools/call',['name'=>'published_consultation_fees'], $token,['Mcp-Name: owner_dashboard_summary']);
    $assert($http===400&&($body['error']['code']??null)===-32020,'Header/body mismatch');
    [$http,$body]=$call('tools/list',[],$token,[],'[]');$assert($http===400,'Batch rejected');
    [$http,$body]=$call('tools/list',[],$token,[],'{');$assert($http===400&&$body['error']['code']===-32700,'Malformed JSON rejected');
    [$http,$body]=$call('tools/list',[],$token,[],str_repeat('x',McpReadService::MAX_BODY+1));$assert($http===413,'Oversized input rejected');
    [$http,$body]=$call('tools/list',[],$token,[],null,'GET');$assert($http===405,'No GET stream');
    $expired=$grants->issue('Expired fixture',1);$ids[]=$expired['id'];
    $store->upsert('mcp_grants',['id'=>$expired['id'],'expires_at'=>gmdate('c',time()-1)]);
    [$http,$body]=$call('tools/list',[],$expired['token']);$assert($http===401,'Expiry rechecked');
    $assert($grants->revoke($grant['id']),'Grant revoked');
    [$http,$body]=$call('tools/call',['name'=>'published_consultation_fees'], $token);$assert($http===401,'Revocation checked at invocation');
    echo "PASS MCP HTTP: grant digest, auth isolation, origin, discovery, typed live fixture, schema, denial, limits, expiry and revoke\n";
}finally{
    if(is_resource($server)){proc_terminate($server);foreach($pipes as $pipe)if(is_resource($pipe))fclose($pipe);proc_close($server);}
    foreach($ids as $id)$store->delete('mcp_grants',$id);
    $store->delete('settings',SettingsService::SETTINGS_ID);
    if($old!==null)$store->upsert('settings',$old);
    SettingsService::flushCache();
}
