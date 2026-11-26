<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Services\{DatabaseService,McpReadService,RateLimiter};

final class McpController {
    public function handle():void {
        // Browser cookies and sessions confer no authority here.
        if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
        header_remove('Set-Cookie');
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        $config=require app_path('config/database.php');
        $url=parse_url((string)($config['app_url']??''));
        $origin=is_array($url)&&isset($url['scheme'],$url['host'])
            ?$url['scheme'].'://'.$url['host'].(isset($url['port'])?':'.$url['port']:''):'';
        $secure=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';
        $loopback=PHP_SAPI==='cli-server'&&in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true);
        $headers=[];
        foreach(['origin','accept','content-type','mcp-protocol-version','mcp-method','mcp-name'] as $key){
            $serverKey=$key==='content-type'?'CONTENT_TYPE':'HTTP_'.strtoupper(str_replace('-','_',$key));
            if(isset($_SERVER[$serverKey]))$headers[$key]=$_SERVER[$serverKey];
        }
        $headers['authorization']=$_SERVER['HTTP_AUTHORIZATION']??$_SERVER['REDIRECT_HTTP_AUTHORIZATION']??'';
        $stream=fopen('php://input','rb');
        $body=$stream?stream_get_contents($stream,McpReadService::MAX_BODY+1):'';
        if($stream)fclose($stream);
        try {
            [$http,$response]=(new McpReadService(new DatabaseService(true),new RateLimiter()))
                ->handle($_SERVER['REQUEST_METHOD']??'',$headers,(string)$body,$origin,$secure||$loopback,$_SERVER['REMOTE_ADDR']??'unknown');
        }catch(\Throwable){$http=503;$response=['jsonrpc'=>'2.0','error'=>['code'=>-32603,'message'=>'MCP service unavailable.']];}
        if($http===401)header('WWW-Authenticate: Bearer realm="Nebo MCP"');
        if($http===405)header('Allow: POST');
        if($http===429)header('Retry-After: 60');
        http_response_code($http);
        echo json_encode($response,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
}
