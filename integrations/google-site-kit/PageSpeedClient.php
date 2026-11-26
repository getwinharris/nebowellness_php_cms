<?php
declare(strict_types=1);
namespace App\Integrations\GoogleSiteKit;

/** Native, owner-triggered PageSpeed v5 read. No WordPress runtime or automatic collection. */
final class PageSpeedClient {
    public const PATHS=['/'=>'Homepage','/about'=>'About'];
    public const STRATEGIES=['mobile'=>'Mobile','desktop'=>'Desktop'];
    private const ENDPOINT='https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
    private const FRESH=1800;
    private const STALE=86400;
    private const MAX_BYTES=8388608;

    public static function target(string $origin,string $path): string {
        if (!isset(self::PATHS[$path])) throw new \InvalidArgumentException('Choose an approved public page.');
        $parts=parse_url($origin);
        if (!is_array($parts)||($parts['scheme']??null)!=='https'||!is_string($parts['host']??null)
            ||!filter_var($parts['host'],FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME)
            ||!str_contains($parts['host'],'.')||filter_var($parts['host'],FILTER_VALIDATE_IP)
            ||isset($parts['user'])||isset($parts['pass'])||isset($parts['query'])||isset($parts['fragment'])
            ||isset($parts['port'])||!in_array($parts['path']??'',['','/'],true)) {
            throw new \InvalidArgumentException('Configure an HTTPS public site origin before running PageSpeed.');
        }
        return 'https://'.strtolower($parts['host']).$path;
    }
    public static function apiKey(mixed $value): string {
        if (!is_string($value)||($value!==''&&!preg_match('/^[A-Za-z0-9_-]{20,128}$/D',$value))) throw new \InvalidArgumentException('Enter a valid Google PageSpeed API key.');
        return $value;
    }
    private static function samePage(string $left,string $right): bool {
        $a=parse_url($left);$b=parse_url($right);
        if (!is_array($a)||!is_array($b)||($a['scheme']??null)!=='https'||($b['scheme']??null)!=='https') return false;
        foreach (['user','pass','query','fragment','port'] as $key) if (isset($a[$key])||isset($b[$key])) return false;
        $host=static fn(array $p):string=>preg_replace('/^www\./i','',strtolower($p['host']??''));
        $path=static fn(array $p):string=>rtrim($p['path']??'/','/')?:'/';
        return $host($a)!==''&&$host($a)===$host($b)&&$path($a)===$path($b);
    }
    private static function numeric(mixed $value): ?float {
        return (is_int($value)||is_float($value))&&is_finite((float)$value)&&$value>=0?(float)$value:null;
    }
    private static function timestamp(mixed $value): ?int {
        if (!is_string($value)||strlen($value)>40||!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?Z$/D',$value)) return null;
        $time=strtotime($value);return $time===false?null:$time;
    }
    private static function field(mixed $data): array {
        $out=[];
        $keys=[
            'LARGEST_CONTENTFUL_PAINT_MS'=>['LCP','ms'],
            'INTERACTION_TO_NEXT_PAINT'=>['INP','ms'],
            'FIRST_CONTENTFUL_PAINT_MS'=>['FCP','ms'],
            'EXPERIMENTAL_TIME_TO_FIRST_BYTE'=>['TTFB','ms'],
            // Preserve API percentile scale rather than invent a normalized CLS.
            'CUMULATIVE_LAYOUT_SHIFT_SCORE'=>['CLS (API percentile)','provider units'],
        ];
        if (!is_array($data)||!is_array($data['metrics']??null)) return $out;
        foreach ($keys as $key=>[$label,$unit]) {
            $metric=$data['metrics'][$key]??null;
            if (!is_array($metric)) continue;
            $value=self::numeric($metric['percentile']??null);
            if ($value===null) continue;
            $category=in_array($metric['category']??null,['FAST','AVERAGE','SLOW','NONE'],true)?$metric['category']:'NONE';
            $out[]=['label'=>$label,'value'=>$value,'unit'=>$unit,'category'=>$category];
        }
        return $out;
    }
    public static function normalize(array $payload,string $target,string $strategy,int $now): array {
        $lh=$payload['lighthouseResult']??null;
        if (!is_array($lh)||!empty($lh['runtimeError'])||!is_string($lh['requestedUrl']??null)||!is_string($lh['finalUrl']??null)
            ||!self::samePage($lh['requestedUrl'],$target)||!self::samePage($lh['finalUrl'],$target)
            ||($lh['configSettings']['formFactor']??null)!==$strategy) throw new \UnexpectedValueException('PageSpeed returned an invalid or different page audit.');
        $score=self::numeric($lh['categories']['performance']['score']??null);
        $at=self::timestamp($payload['analysisUTCTimestamp']??$lh['fetchTime']??null);
        if ($score===null||$score>1||$at===null||$at>$now+300||$at<$now-86400*7) throw new \UnexpectedValueException('PageSpeed report is incomplete.');
        $lab=[];
        foreach (['first-contentful-paint'=>['FCP','ms'],'largest-contentful-paint'=>['LCP','ms'],
            'total-blocking-time'=>['Total blocking time','ms'],'speed-index'=>['Speed index','ms'],'cumulative-layout-shift'=>['CLS','score']] as $id=>[$label,$unit]) {
            $value=self::numeric($lh['audits'][$id]['numericValue']??null);
            if ($value!==null) $lab[]=['label'=>$label,'value'=>$value,'unit'=>$unit];
        }
        // Field scopes may be absent, or Google may fall back from a page
        // to an origin. Never present an origin aggregate as page-specific.
        $page=$payload['loadingExperience']??null;
        $origin=$payload['originLoadingExperience']??null;
        $base='https://'.parse_url($target,PHP_URL_HOST).'/';
        $pageMatches=is_array($page)&&empty($page['origin_fallback'])&&is_string($page['id']??null)&&self::samePage($page['id'],$target);
        $originMatches=is_array($origin)&&is_string($origin['id']??null)&&self::samePage($origin['id'],$base);
        return ['target'=>$target,'strategy'=>$strategy,'score'=>round($score*100,1),'analysis_at'=>$at,
            'lab'=>$lab,'page_field'=>$pageMatches?self::field($page):[],
            'origin_field'=>$originMatches?self::field($origin):[]];
    }
    private static function identity(string $origin,string $key): string {return hash('sha256',$origin."\0".$key);}
    public function results(string $origin,string $key,array &$cache,int $now): array {
        if (($cache['identity']??null)!==self::identity($origin,$key)) $cache=['identity'=>self::identity($origin,$key),'reports'=>[]];
        $reports=$cache['reports']??[];
        if (!is_array($reports)) $reports=[];
        foreach ($reports as $id=>&$report) {
            if (!is_array($report)||!isset($report['state'])) {unset($reports[$id]);continue;}
            if (isset($report['data'])&&($report['retrieved']??0)<$now-self::STALE) {
                $report=['state'=>'unavailable','retrieved'=>null,'data'=>null];
            } elseif (($report['state']??null)==='ready'&&($report['retrieved']??0)<$now-self::FRESH) $report['state']='stale';
        }
        unset($report);
        $cache['reports']=$reports;return $reports;
    }
    public function run(string $origin,string $path,string $strategy,string $key,array &$cache,?callable $get=null,?int $now=null): array {
        $now??=time();
        if (!isset(self::STRATEGIES[$strategy])) throw new \InvalidArgumentException('Select mobile or desktop.');
        $target=self::target($origin,$path);$key=self::apiKey($key);
        $this->results($origin,$key,$cache,$now);$id=$path.'|'.$strategy;
        $prior=$cache['reports'][$id]??null;
        if (($prior['state']??null)==='ready'&&($prior['retrieved']??0)>=$now-self::FRESH) return $prior;
        $state=['state'=>'unavailable','retrieved'=>null,'data'=>null];
        try {
            $params=['url'=>$target,'strategy'=>$strategy,'category'=>'performance','locale'=>'en'];
            if ($key!=='') $params['key']=$key;
            $payload=($get??self::get(...))(self::ENDPOINT,$params);
            $data=self::normalize($payload,$target,$strategy,$now);
            $state=['state'=>'ready','retrieved'=>$now,'data'=>$data];
        } catch (\Throwable $error) {
            $code=$error->getCode();
            if (in_array($code,[400,401,403,404],true)) $state['state']='access';
            elseif ($code===429) $state['state']='quota';
            // Do not reuse results following an access denial or config change.
            if ($state['state']!=='access'&&isset($prior['data'])&&($prior['retrieved']??0)>=$now-self::STALE) {
                $state=$prior;$state['state']='stale';
                $state['last_error']=$code===429?'quota':'unavailable';
            }
        }
        $cache['reports'][$id]=$state;return $state;
    }
    private static function get(string $endpoint,array $params): array {
        $ch=curl_init($endpoint.'?'.http_build_query($params,'','&',PHP_QUERY_RFC3986));
        if ($ch===false) throw new \RuntimeException('PageSpeed unavailable.');
        $body='';$tooLarge=false;
        curl_setopt_array($ch,[CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>22,
            CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_HTTPHEADER=>['Accept: application/json'],
            CURLOPT_WRITEFUNCTION=>static function($handle,string $chunk)use(&$body,&$tooLarge):int {
                if (strlen($body)+strlen($chunk)>self::MAX_BYTES) {$tooLarge=true;return 0;}
                $body.=$chunk;return strlen($chunk);
            }]);
        try {
            $ok=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
        } finally {curl_close($ch);}
        if ($tooLarge||$ok===false) throw new \RuntimeException('PageSpeed unavailable.');
        $payload=json_decode($body,true);
        if ($status===403&&(($payload['error']['status']??null)==='RESOURCE_EXHAUSTED'||array_intersect(array_column((array)($payload['error']['errors']??[]),'reason'),['rateLimitExceeded','userRateLimitExceeded','quotaExceeded','dailyLimitExceeded']))) $status=429;
        if ($status!==200||!is_array($payload)||isset($payload['error'])) throw new \RuntimeException('PageSpeed request failed.',$status);
        return $payload;
    }
}
