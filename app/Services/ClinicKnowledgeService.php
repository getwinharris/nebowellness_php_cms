<?php
declare(strict_types=1);
namespace App\Services;

/** Read-only, public records only; no owner operations or customer identities. */
final class ClinicKnowledgeService {
    public function __construct(private DatabaseService $store = new DatabaseService(),private ?BlogService $blog=null) {}
    private static function publicText(mixed $value,int $max,bool $required=false): string {
        if ($value===null&&!$required) return '';
        if (!is_string($value)||!mb_check_encoding($value,'UTF-8')||mb_strlen($value)>$max||
            ($required&&trim($value)==='')||preg_match('/[\\x00-\\x08\\x0b\\x0c\\x0e-\\x1f\\x7f]/u',$value)) {
            throw new \DomainException('Invalid published clinic field.');
        }
        return $value;
    }

    public function practitioners(): array {
        return array_map(static fn(array $row): array=>[
            'name'=>self::publicText($row['name']??null,200,true),
            'credentials'=>self::publicText($row['credentials']??null,1000),
            'speciality'=>self::publicText($row['speciality']??null,300),
            'registration_number'=>self::publicText($row['registration_number']??null,80),
            'url'=>'/#team',
        ],array_slice((new ConsultantService($this->store))->all(),0,40));
    }
    public function programmes(): array {
        if (empty((new SettingsService($this->store))->strictModules()['campaigns'])) return [];
        $pages=(new CampaignPageService($this->store))->published();
        // Validate source values before homepage projection can coerce malformed arrays.
        foreach ($pages as $page) {
            if (!in_array($page['homepage_program']??false,[true,1,'1'],true)) continue;
            self::publicText($page['title']??null,200,true);
            self::publicText($page['summary']??null,6000);
            $slug=self::publicText($page['slug']??null,120,true);
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)) throw new \DomainException('Invalid published programme slug.');
        }
        return array_map(static fn(array $row):array=>[
            'title'=>self::publicText($row['title']??null,200,true),
            'summary'=>self::publicText($row['copy']??null,6000),
            'duration'=>self::publicText($row['duration']??null,80),
            'url'=>'/campaigns/'.$row['slug'],
        ],array_slice(CampaignPageService::homepagePrograms($pages),0,30));
    }
    /** Resolve exactly one published record. An absent, draft or ambiguous slug is not found. */
    public function programmeDetail(string $slug): ?array {
        if (strlen($slug)>120||!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)) throw new \InvalidArgumentException('Invalid programme slug.');
        if (empty((new SettingsService($this->store))->strictModules()['campaigns'])) throw new \DomainException('Campaigns are unavailable.');
        $page=(new CampaignPageService($this->store))->findPublished($slug);
        if ($page===null) return null;
        $body=$page['body']??'';
        if (!is_string($body)||!mb_check_encoding($body,'UTF-8')) throw new \DomainException('Invalid programme body.');
        return ['title'=>self::publicText($page['title']??null,200,true),
            'summary'=>self::publicText($page['summary']??null,6000),
            'duration'=>self::publicText($page['program_duration']??null,80),
            'url'=>'/campaigns/'.$slug,
            'body'=>self::publicText(mb_substr(strip_tags($body),0,12000),12000)];
    }
    /** Local lexical knowledge RAG. Published records remain the source of truth. */
    public function search(string $query): array {
        $modules=(new SettingsService($this->store))->strictModules();
        $documents=[];
        $catalogue=(new ConsultationFeeService($this->store))->published();
        foreach ($catalogue['fees'] as $fee) $documents[]=['type'=>'consultation_fee','title'=>$fee['title'],'url'=>ConsultationFeeService::SOURCE,'text'=>$fee['display_price'].' | '.$fee['duration'].' | '.$fee['description']."\n".implode("\n",array_merge($fee['inclusions'],$catalogue['inclusions']))];
        foreach ($this->practitioners() as $row) {
            $documents[]=['type'=>'practitioner','title'=>$row['name'],'url'=>$row['url'],
                'text'=>implode(' | ',array_filter([$row['credentials'],$row['speciality'],
                    $row['registration_number']!==''?'Registration number: '.$row['registration_number']:'']))];
        }
        if (!empty($modules['campaigns'])) foreach (array_slice((new CampaignPageService($this->store))->published(),0,80) as $row) {
            $slug=self::publicText($row['slug']??null,120,true);
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)) throw new \DomainException('Invalid programme slug.');
            $body=$row['body']??'';
            if (!is_string($body)||!mb_check_encoding($body,'UTF-8')) throw new \DomainException('Invalid programme body.');
            $documents[]=['type'=>'programme','title'=>self::publicText($row['title']??null,200,true),'url'=>'/campaigns/'.$slug,
                'text'=>implode("\n",[self::publicText($row['summary']??null,6000),self::publicText($row['program_duration']??null,80),mb_substr($body,0,16000)])];
        }
        if (!empty($modules['blog'])) foreach (array_slice(($this->blog??new BlogService())->all(false,$modules),0,80) as $row) {
            $article=self::article($row);
            $body=$row['content']??'';
            if (!is_string($body)||!mb_check_encoding($body,'UTF-8')) throw new \DomainException('Invalid article body.');
            $documents[]=['type'=>'article','title'=>$article['title'],'url'=>$article['path'],
                'text'=>self::publicText($row['summary']??$row['excerpt']??null,6000)."\n".mb_substr($body,0,16000)];
        }
        return ['matches'=>self::rank($query,$documents),'method'=>'lexical title and passage matching','scope'=>'Published clinic records only'];
    }

    private static function article(array $row): array {
        $slug=self::publicText($row['slug']??null,120,true);
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)) throw new \DomainException('Invalid published article slug.');
        return ['title'=>self::publicText($row['title']??null,200,true),'path'=>'/blog/'.$slug,
            'category'=>self::publicText($row['category']??null,120)];
    }

    public function searchArticles(string $query): array {
        $modules=(new SettingsService($this->store))->strictModules();
        if (empty($modules['blog'])) throw new \DomainException('Blog unavailable.');
        $words=self::words($query,2);
        $scored=[];
        foreach (($this->blog??new BlogService())->all(false,$modules) as $row) {
            $article=self::article($row);
            $title=mb_strtolower($article['title']);
            $summary=mb_strtolower(self::publicText($row['excerpt']??$row['summary']??null,6000));
            $score=0;
            foreach ($words as $word) {
                if (str_contains($title,$word)) $score+=2;
                if (str_contains($summary,$word)) $score++;
            }
            if ($score>0) $scored[]=$article+['score'=>$score];
        }
        usort($scored,static fn(array $a,array $b):int=>($b['score']<=>$a['score'])?:strcmp($a['path'],$b['path']));
        return ['found'=>count($scored),'articles'=>array_slice($scored,0,5)];
    }

    private static function words(string $query,int $minimum=3): array {
        return array_slice(array_values(array_unique(array_filter(
            preg_split('/[^\p{L}\p{N}\p{M}]+/u',mb_strtolower(trim($query)))?:[],
            static fn(string $word):bool=>mb_strlen($word)>=$minimum&&!in_array($word,['the','what','which','who','where','when','how','about','tell','please','and','for','your','with','does','have','can','this','that','from','more','need'],true)
        ))),0,40);
    }

    public static function rank(string $query,array $documents): array {
        $lower=mb_strtolower(trim($query));
        $words=self::words($query,2);
        if (!$words) return [];
        $hits=[];
        foreach ($documents as $document) {
            $title=mb_strtolower((string)$document['title']);
            $text=mb_substr(trim(strip_tags((string)$document['text'])),0,16000);
            $titleScore=0;
            foreach ($words as $word) if (str_contains($title,$word)) $titleScore+=4;
            $chunks=preg_split('/\R{2,}/u',$text)?:[$text]; $best=null;
            foreach ($chunks as $chunk) {
                $chunk=trim($chunk); if ($chunk==='') continue;
                $normalized=mb_strtolower($chunk); $score=$titleScore;
                foreach ($words as $word) if (str_contains($normalized,$word)) $score++;
                if ($score>0&&($best===null||$score>$best['score'])) $best=['score'=>$score,'excerpt'=>mb_substr($chunk,0,1800)];
            }
            if ($best===null && $titleScore>0) $best=['score'=>$titleScore,'excerpt'=>''];
            if ($best===null) continue;
            if ($document['type']==='practitioner' && $titleScore===0) continue;
            if ($title!=='' && str_contains($lower,preg_replace('/^dr\.?\s+/i','',$title))) $best['score']+=8;
            $hits[]=['source_type'=>$document['type'],'title'=>$document['title'],'url'=>$document['url']]+$best;
        }
        usort($hits,static fn(array $a,array $b):int=>($b['score']<=>$a['score'])?:strcmp($a['url'],$b['url']));
        return array_slice($hits,0,5);
    }
}
