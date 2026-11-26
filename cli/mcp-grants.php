<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(getenv('BAPX_TEST_MODE')==='1'){fwrite(STDERR,"MCP grant management requires durable storage.\n");exit(1);}
require dirname(__DIR__).'/app/bootstrap.php';
use App\Services\{DatabaseService,McpGrantService};
try{
    $service=new McpGrantService(new DatabaseService());
    $action=$argv[1]??'list';
    if($action==='list'&&count($argv)<=2)$result=$service->listing();
    elseif($action==='issue'&&count($argv)===4&&ctype_digit($argv[3])){
        if(!stream_isatty(STDOUT))throw new RuntimeException('Issue credentials only in a private interactive terminal.');
        $result=$service->issue($argv[2],(int)$argv[3]);
        fwrite(STDERR,"Save this one-time token privately. Never paste it into an issue, chat or log.\n");
    }elseif($action==='revoke'&&count($argv)===3)$result=['revoked'=>$service->revoke($argv[2])];
    else throw new InvalidArgumentException('Usage: bapXcms mcp:grants list | issue LABEL DAYS | revoke ID');
    echo json_encode($result,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR),PHP_EOL;
}catch(Throwable){fwrite(STDERR,"MCP grant operation rejected or unavailable. Check the command and authorized database access.\n");exit(1);}
