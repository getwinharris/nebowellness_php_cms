#!/usr/bin/env php
<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit(1);}
$root=$argv[1]??dirname(__DIR__);$editSlug=$argv[2]??'';
require $root.'/app/bootstrap.php';
$blog=new App\Services\BlogService();
try{
    $existing=$editSlug!==''?$blog->find($editSlug):[];
    if($existing===null)throw new DomainException('Article not found.');
    $ask=static function(string $label,string $default=''):string{
        $value=readline($label.' ['.$default.']: ');
        if($value===false)throw new RuntimeException('Input cancelled.');
        return trim($value)===''?$default:trim($value);
    };
    $title=$ask('Title',(string)($existing['title']??''));
    if($title==='')throw new InvalidArgumentException('Title required.');
    $slug=$editSlug!==''?$editSlug:$ask('New permanent slug',trim((string)preg_replace('/[^a-z0-9]+/','-',strtolower($title)),'-'));
    $summary=$ask('Summary',(string)($existing['excerpt']??''));
    echo "Markdown body; enter . on its own line to finish:\n";
    $lines=[];while(true){$line=readline();if($line===false)throw new RuntimeException('Input cancelled.');if($line==='.')break;$lines[]=$line;}
    $content=$lines?implode("\n",$lines):(string)($existing['content']??'');
    if(trim($content)==='')throw new InvalidArgumentException('Content required.');
    $data=['slug'=>$slug,'title'=>$title,'category'=>$existing['category']??'help','excerpt'=>$summary,'content'=>$content,'updated_at'=>date('Y-m-d')];
    if(!$existing)$data+=['published'=>false,'published_at'=>date('Y-m-d'),'template'=>'help'];
    $saved=$blog->save($data,(string)($existing['_revision']??'new'));
    echo "Saved /blog/{$saved['slug']} — ".App\Services\BlogService::publicationState($saved)."\n";
}catch(Throwable $e){fwrite(STDERR,"Article not saved: ".$e->getMessage()."\n");exit(1);}
