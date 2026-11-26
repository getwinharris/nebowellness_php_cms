<?php
declare(strict_types=1);
namespace App\Services;

/** Read-only, public records only; no owner operations or customer identities. */
final class ClinicKnowledgeService {
    public function __construct(private DatabaseService $store = new DatabaseService()) {}
    public function practitioners(): array {
        return array_map(static fn(array $row): array=>[
            'name'=>(string)($row['name']??''),
            'credentials'=>(string)($row['credentials']??''),
            'speciality'=>(string)($row['speciality']??''),
            'registration_number'=>(string)($row['registration_number']??''),
            'url'=>'/#team',
        ],array_slice((new ConsultantService($this->store))->all(),0,40));
    }
    public function programmes(): array {
        if (empty((new SettingsService($this->store))->strictModules()['campaigns'])) return [];
        return array_map(static fn(array $row):array=>[
            'title'=>(string)$row['title'],'summary'=>(string)($row['copy']??''),
            'duration'=>(string)($row['duration']??''),'url'=>'/campaigns/'.$row['slug'],
        ],array_slice(CampaignPageService::homepagePrograms((new CampaignPageService($this->store))->published()),0,30));
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
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',(string)$row['slug'])) continue;
            $documents[]=['type'=>'programme','title'=>(string)$row['title'],'url'=>'/campaigns/'.$row['slug'],
                'text'=>implode("\n",[(string)($row['summary']??''),(string)($row['program_duration']??''),(string)($row['body']??'')])];
        }
        if (!empty($modules['blog'])) foreach (array_slice((new BlogService())->all(false),0,80) as $row) {
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',(string)($row['slug']??''))) continue;
            $documents[]=['type'=>'article','title'=>(string)($row['title']??''),'url'=>'/blog/'.$row['slug'],
                'text'=>(string)($row['summary']??$row['excerpt']??'')."\n".(string)($row['content']??'')];
        }
        return ['matches'=>self::rank($query,$documents),'method'=>'lexical title and passage matching','scope'=>'Published clinic records only'];
    }

    public static function rank(string $query,array $documents): array {
        $lower=mb_strtolower(trim($query));
        $words=array_values(array_unique(array_filter(preg_split('/[^\p{L}\p{N}]+/u',$lower)?:[],static fn(string $word):bool=>mb_strlen($word)>=3&&!in_array($word,['the','what','which','who','where','when','how','about','tell','please','and','for','your','with','does','have','can','this','that','from','more','need'],true))));
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
