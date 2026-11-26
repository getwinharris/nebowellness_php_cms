#!/usr/bin/env php
<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit(1);}
$root=$argv[1]??dirname(__DIR__);$editSlug=$argv[3]??'';
require $root.'/app/bootstrap.php';
$blog=new App\Services\BlogService();
$existing=[];
if($editSlug!==''){
    $existing=$blog->find($editSlug);
    if($existing===null){fwrite(STDERR,"Article not found.\n");exit(1);}
}
function prompt(string $label,string $default=''): string {
    $value=readline($label.' ['.$default.']: ');
    if($value===false)throw new RuntimeException('Input cancelled.');
    return $value===''?$default:$value;
}
try {
    echo "Markdown article editor — revision checked\n";
    $title=prompt('Title',(string)($existing['title']??''));
    if(trim($title)==='')throw new InvalidArgumentException('Title required.');
    $auto=trim((string)preg_replace('/[^a-z0-9]+/','-',strtolower($title)),'-');
    $slug=$editSlug!==''?$editSlug:prompt('New permanent slug',$auto);
    if(!App\Services\BlogService::validSlug($slug))throw new InvalidArgumentException('Invalid slug.');
    echo "Categories: ".implode(', ',array_column($blog->categories(),'slug'))."\n";
    $data=['slug'=>$slug,'title'=>$title,
        'category'=>prompt('Category',(string)($existing['category']??'')),
        'author'=>prompt('Author',(string)($existing['author']??'')),
        'published_at'=>prompt('Date (YYYY-MM-DD)',(string)($existing['published_at']??date('Y-m-d'))),
        'excerpt'=>prompt('Excerpt',(string)($existing['excerpt']??'')),
        'updated_at'=>date('Y-m-d')];
    $published=prompt('Publish when due? (y/n)',($existing['published']??false)?'y':'n');
    if(!in_array($published,['y','n'],true))throw new InvalidArgumentException('Choose y or n.');
    $data['published']=$published==='y';
    echo "Markdown body; enter . on its own line to finish. Enter . immediately to keep the existing body.\n";
    $lines=[];while(true){$line=readline();if($line===false)throw new RuntimeException('Input cancelled.');if($line==='.')break;$lines[]=$line;}
    $data['content']=$lines?implode("\n",$lines):(string)($existing['content']??'');
    if(trim($data['content'])==='')throw new InvalidArgumentException('Content required.');
    $saved=$blog->save($data,(string)($existing['_revision']??'new'));
    echo "Saved /blog/{$saved['slug']} — ".App\Services\BlogService::publicationState($saved)."\n";
}catch(Throwable $e){fwrite(STDERR,"Article not saved: ".$e->getMessage()."\n");exit(1);}
