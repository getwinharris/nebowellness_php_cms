<?php
declare(strict_types=1);
namespace App\Services;

use stdClass;

/**
 * Stateless, request-metadata MCP 2026-07-28 subset. Published read capabilities
 * only. NativeAbilityRegistry owns input/output schema and module validation.
 */
final class McpReadService {
    public const VERSION='2026-07-28';
    public const MAX_BODY=32768;
    public const PUBLIC_NAMES=[
        'published_consultation_fees','published_practitioners','published_programmes',
        'published_programme_detail','search_clinic_knowledge','search_articles',
    ];

    public function __construct(private DatabaseService $store,private RateLimiter $limiter) {}

    public function handle(string $method,array $headers,string $body,string $allowedOrigin,bool $secure,string $ip): array {
        if(isset($headers['origin'])&&($allowedOrigin===''||$headers['origin']!==$allowedOrigin))
            return self::error(403,-32600,'Origin is not permitted.');
        if(!$secure)return self::error(403,-32600,'A secure transport is required.');
        if($method!=='POST')return self::error(405,-32600,'POST required.');
        $auth=$headers['authorization']??'';
        if(!is_string($auth)||strlen($auth)>180||!preg_match('/\ABearer nebo_mcp_[0-9a-f]{32}\.[0-9a-f]{64}\z/i',$auth))
            return self::error(401,-32600,'Valid MCP client authorization required.');
        try {
            if(!$this->limiter->consume('mcp-auth:'.hash('sha256',$ip),60,60))
                return self::error(429,-32600,'Request limit exceeded.');
            $grant=(new McpGrantService($this->store))->authorize($auth);
            if(!$grant)return self::error(401,-32600,'Valid MCP client authorization required.');
            if(!$this->limiter->consume('mcp-grant:'.$grant['id'],30,60))
                return self::error(429,-32600,'Request limit exceeded.');
        } catch(\Throwable) { return self::error(503,-32603,'Authorization unavailable.'); }

        if(strlen($body)>self::MAX_BODY)return self::error(413,-32600,'Request too large.');
        if(strtolower(trim(explode(';',$headers['content-type']??'')[0]))!=='application/json')
            return self::error(415,-32600,'JSON content required.');
        $accept=array_map(static fn($v)=>strtolower(trim(explode(';',$v)[0])),explode(',',$headers['accept']??''));
        if(!in_array('application/json',$accept,true)||!in_array('text/event-stream',$accept,true))return self::error(406,-32600,'Both MCP response media types must be accepted.');
        try { $msg=json_decode($body,false,32,JSON_THROW_ON_ERROR); }
        catch(\JsonException) {return self::error(400,-32700,'Invalid JSON.');}
        $id=$msg instanceof stdClass?$msg->id??null:null;
        $validId=is_int($id)&&abs($id)<=9007199254740991||is_string($id)&&strlen($id)<=128;
        if(!$validId||!($msg instanceof stdClass)||($msg->jsonrpc??null)!=='2.0'||
            !is_string($msg->method??null)||!(($msg->params??null) instanceof stdClass)||
            array_diff(array_keys(get_object_vars($msg)),['jsonrpc','id','method','params']))
            return self::error(400,-32600,'Invalid request.',$validId?$id:null);
        $params=$msg->params;
        $meta=$params->_meta??null;
        if(!$meta instanceof stdClass||!is_string($meta->{'io.modelcontextprotocol/protocolVersion'}??null)||
            !(($meta->{'io.modelcontextprotocol/clientInfo'}??null) instanceof stdClass)||
            !(($meta->{'io.modelcontextprotocol/clientCapabilities'}??null) instanceof stdClass))
            return self::error(400,-32602,'Required request metadata missing.',$id);
        $client=$meta->{'io.modelcontextprotocol/clientInfo'};
        if(!is_string($client->name??null)||$client->name===''||strlen($client->name)>160||
            !is_string($client->version??null)||$client->version===''||strlen($client->version)>80)
            return self::error(400,-32602,'Invalid client metadata.',$id);
        $version=$meta->{'io.modelcontextprotocol/protocolVersion'};
        if(($headers['mcp-protocol-version']??null)!==$version||($headers['mcp-method']??null)!==$msg->method)
            return self::error(400,-32020,'Request metadata header mismatch.',$id);
        if($version!==self::VERSION)
            return self::error(400,-32022,'Unsupported protocol version.',$id,['supported'=>[self::VERSION],'requested'=>substr($version,0,80)]);
        if($msg->method==='tools/call'){
            $headerName=$headers['mcp-name']??null;
            if(is_string($headerName)&&preg_match('/\A=\?base64\?([A-Za-z0-9+\/=]+)\?=\z/',$headerName,$encoded))
                $headerName=base64_decode($encoded[1],true);
            if(!is_string($params->name??null)||$headerName!==$params->name)
                return self::error(400,-32020,'Tool name header mismatch.',$id);
        }
        if(!in_array($msg->method,['server/discover','tools/list','tools/call'],true))
            return self::error(404,-32601,'Method not found.',$id);

        if($msg->method==='server/discover'){
            if(array_diff(array_keys(get_object_vars($params)),['_meta']))return self::error(400,-32602,'Invalid discovery parameters.',$id);
            return self::result($id,['supportedVersions'=>[self::VERSION],'capabilities'=>['tools'=>(object)[]],
                '_meta'=>['io.modelcontextprotocol/serverInfo'=>['name'=>'Nebo Clinic','version'=>'1.0.0']],
                'instructions'=>'Authorized reads of published clinic content only. No owner or customer records, bookings, or mutations.',
                'ttlMs'=>0,'cacheScope'=>'private']);
        }
        try {
            $registry=new NativeAbilityRegistry($this->store,'support',(new SettingsService($this->store))->strictModules());
            $tools=[];
            foreach($registry->discover() as $definition){
                if(in_array($definition['name'],self::PUBLIC_NAMES,true)&&$definition['category']==='published-clinic'&&
                    ($definition['annotations']['readonly']??false)===true)$tools[$definition['name']]=$definition;
            }
            ksort($tools,SORT_STRING);
            if($msg->method==='tools/list'){
                if(array_diff(array_keys(get_object_vars($params)),['_meta']))return self::error(400,-32602,'Invalid list parameters.',$id);
                $list=[];
                foreach($tools as $name=>$d)$list[]=['name'=>$name,'description'=>$d['description'],
                    'inputSchema'=>$d['input_schema'],'outputSchema'=>$d['output_schema'],
                    'annotations'=>['readOnlyHint'=>true,'destructiveHint'=>false,'idempotentHint'=>true,'openWorldHint'=>false]];
                return self::result($id,['tools'=>$list,'ttlMs'=>0,'cacheScope'=>'private']);
            }
            if(!isset($tools[$params->name]))return self::error(400,-32602,'Unknown or unavailable tool.',$id);
            if(array_diff(array_keys(get_object_vars($params)),['_meta','name','arguments'])||
                (property_exists($params,'arguments')&&!($params->arguments instanceof stdClass)))
                return self::error(400,-32602,'Invalid tool arguments.',$id);
            $args=json_decode(json_encode($params->arguments??(object)[],JSON_THROW_ON_ERROR),true,32,JSON_THROW_ON_ERROR);
            // No arbitrary tool arguments or caller-supplied scopes reach the domain layer.
            $result=$registry->invoke($params->name,$args);
            if(isset($result['error']))return self::result($id,['isError'=>true,'content'=>[
                ['type'=>'text','text'=>'The tool could not complete the authorized read.']]]);
            return self::result($id,['isError'=>false,'structuredContent'=>(object)$result,
                'content'=>[['type'=>'text','text'=>json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]]]);
        }catch(\Throwable){return self::error(503,-32603,'Published capabilities unavailable.',$id);}
    }

    private static function result(int|string $id,array $value):array {
        return [200,['jsonrpc'=>'2.0','id'=>$id,'result'=>['resultType'=>'complete']+$value]];
    }

    private static function error(int $http,int $code,string $message,int|string|null $id=null,?array $data=null):array {
        $error=['code'=>$code,'message'=>$message];
        if($data!==null)$error['data']=$data;
        return [$http,['jsonrpc'=>'2.0']+($id===null?[]:['id'=>$id])+['error'=>$error]];
    }
}
