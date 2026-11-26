<?php
declare(strict_types=1);
namespace App\Services;

/** Published display fees, not payable session offers or appointment reservations. */
final class ConsultationFeeService {
    public const CURRENCIES=['INR'=>'₹','USD'=>'USD ','EUR'=>'€','GBP'=>'£','SGD'=>'SGD ','AED'=>'AED ','AUD'=>'AUD ','CAD'=>'CAD '];
    public const LIMIT=20;
    public const SOURCE='/contact#consultation-fees';
    public const PUBLIC_SCOPE='Published consultation display fees only. An enquiry does not confirm an appointment or initiate payment.';
    public function __construct(private DatabaseService $store=new DatabaseService()) {}

    private static function text(mixed $value,int $limit,bool $required=false): string {
        if (!is_string($value)||!mb_check_encoding($value,'UTF-8')) throw new \InvalidArgumentException('Enter valid text.');
        $value=trim($value);
        if (mb_strlen($value)>$limit||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/u',$value)||($required&&$value==='')) throw new \InvalidArgumentException('Check required text and length limits.');
        return $value;
    }
    public static function lines(mixed $value): array {
        if (!is_string($value)) throw new \InvalidArgumentException('Enter one inclusion per line.');
        $lines=array_values(array_filter(array_map('trim',preg_split('/\R/u',self::text($value,4000))?:[]),static fn(string $line):bool=>$line!==''));
        if (count($lines)>20) throw new \InvalidArgumentException('Use at most 20 inclusions.');
        return array_map(static fn(string $line):string=>self::text($line,200,true),$lines);
    }
    public static function minorUnits(mixed $amount): int {
        if (!is_string($amount)||!preg_match('/^(?:0|[1-9][0-9]{0,6})(?:\.[0-9]{1,2})?$/D',$amount)) throw new \InvalidArgumentException('Enter a nonnegative amount with at most two decimal places.');
        $parts=explode('.',$amount,2);
        return (int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');
    }
    private static function whole(mixed $value,int $min,int $max): int {
        if ((!is_string($value)&&!is_int($value))||!preg_match('/^[0-9]{1,4}$/D',(string)$value)|| (int)$value<$min||(int)$value>$max) throw new \InvalidArgumentException('Check duration and display order.');
        return (int)$value;
    }
    public static function normalize(array $input,string $id): array {
        if (!preg_match('/^[a-z0-9][a-z0-9-]{0,39}$/D',$id)) throw new \InvalidArgumentException('Invalid fee record.');
        $currency=$input['currency']??null;
        if (!is_string($currency)||!isset(self::CURRENCIES[$currency])) throw new \InvalidArgumentException('Select a supported currency.');
        $status=$input['status']??null;
        if (!in_array($status,['draft','published'],true)) throw new \InvalidArgumentException('Select draft or published.');
        $min=self::whole($input['min_minutes']??null,1,1440);$max=self::whole($input['max_minutes']??null,1,1440);
        if ($max<$min) throw new \InvalidArgumentException('Maximum duration cannot be below minimum duration.');
        return ['id'=>$id,'title'=>self::text($input['title']??null,100,true),'description'=>self::text($input['description']??'',500),
            'amount_minor'=>self::minorUnits($input['amount']??null),'currency'=>$currency,'min_minutes'=>$min,'max_minutes'=>$max,
            'display_order'=>self::whole($input['display_order']??0,0,9999),'status'=>$status,'inclusions'=>self::lines($input['inclusions']??'')];
    }
    public static function isFeeQuestion(string $question): bool {
        return (bool)preg_match('/\b(fees?|prices?|pricing|cost|charges?|included|inclusions?|how much|how long|duration|minutes)\b/i',$question)
            && (bool)preg_match('/\b(consultations?|follow[ -]?up|initial|online|fees?)\b/i',$question);
    }
    public static function matchesNamedPrice(string $question,string $answer,array $catalogue): bool {
        if (!preg_match('/\b(fees?|prices?|pricing|cost|charges?|how much)\b/i',$question)) return true;
        $question=mb_strtolower($question);
        $answer=preg_replace('/(?<=\d),(?=\d)/','',$answer)??$answer;
        foreach ($catalogue['fees']??[] as $fee) {
            $title=mb_strtolower((string)$fee['title']);
            if ($title===''||!str_contains($question,$title)) continue;
            $minor=(int)$fee['amount_minor'];
            $whole=(string)intdiv($minor,100);
            $fraction=$minor%100;
            $pattern=preg_quote($whole,'/').($fraction?'\.'.preg_quote(rtrim(str_pad((string)$fraction,2,'0',STR_PAD_LEFT),'0'),'/').($fraction%10===0?'0?':''):'(?:\.0{1,2})?');
            if (!preg_match('/(?<![0-9.])'.$pattern.'(?![0-9]|\.[0-9])/',$answer)) return false;
        }
        return true;
    }

    public static function amountInput(int $minor): string {
        return intdiv($minor,100).'.'.str_pad((string)($minor%100),2,'0',STR_PAD_LEFT);
    }
    public static function price(array $row): string {
        $minor=(int)$row['amount_minor'];
        return self::CURRENCIES[$row['currency']].number_format(intdiv($minor,100)).($minor%100?'.'.str_pad((string)($minor%100),2,'0',STR_PAD_LEFT):'');
    }
    public static function duration(array $row): string {
        return $row['min_minutes'].($row['min_minutes']===$row['max_minutes']?'':'–'.$row['max_minutes']).' minutes';
    }
    /** Validate stored data too; a malformed record must not display a false fee. */
    public static function fromSettings(array $settings,bool $publishedOnly=true): array {
        $rows=$settings['consultation_fees']??[];
        $shared=$settings['consultation_inclusions']??[];
        if (!is_array($rows)||!array_is_list($rows)||count($rows)>self::LIMIT||!is_array($shared)||!array_is_list($shared)||count(array_filter($shared,'is_string'))!==count($shared)) throw new \DomainException('Invalid consultation catalogue.');
        $out=[];$seen=[];
        foreach ($rows as $row) {
            if (!is_array($row)||!is_int($row['amount_minor']??null)||$row['amount_minor']<0||$row['amount_minor']>999999999||!is_array($row['inclusions']??null)||!array_is_list($row['inclusions'])||count(array_filter($row['inclusions'],'is_string'))!==count($row['inclusions'])||!is_string($row['id']??null)||isset($seen[$row['id']])) throw new \DomainException('Invalid consultation record.');
            $seen[$row['id']]=true;
            $row=self::normalize(array_replace($row,['amount'=>self::amountInput($row['amount_minor']),'inclusions'=>implode("\n",$row['inclusions'])]),$row['id']);
            if ($publishedOnly&&$row['status']!=='published') continue;
            $out[]=$row;
        }
        usort($out,static fn(array $a,array $b):int=>($a['display_order']<=>$b['display_order'])?:strcmp($a['id'],$b['id']));
        return ['fees'=>$out,'inclusions'=>self::lines(implode("\n",$shared))];
    }
    public function settings(): array {
        $settings=$this->store->find('settings',SettingsService::SETTINGS_ID);
        if (!$settings) throw new \DomainException('Site settings are unavailable.');
        return $settings;
    }
    public function published(): array {
        $catalog=self::fromSettings($this->settings());
        $catalog['fees']=array_map(static fn(array $row):array=>array_diff_key($row,['status'=>true,'display_order'=>true])+['display_price'=>self::price($row),'duration'=>self::duration($row)],$catalog['fees']);
        if (!$catalog['fees']) $catalog['inclusions']=[];
        return $catalog+['source'=>self::SOURCE,'scope'=>self::PUBLIC_SCOPE];
    }
    public function save(array $input,string $revision): void {
        $settings=$this->settings();
        $catalog=self::fromSettings($settings,false);
        $patch=['id'=>SettingsService::SETTINGS_ID];
        if (($input['fee_action']??'')==='inclusions') {
            $patch['consultation_inclusions']=self::lines($input['shared_inclusions']??null);
        } elseif (($input['fee_action']??'')==='save') {
            $id=$input['fee_id']??'';
            if (!is_string($id)) throw new \InvalidArgumentException('Invalid fee identifier.');
            $rows=$catalog['fees'];$existing=null;
            foreach ($rows as $index=>$row) if ($row['id']===$id) {$existing=$index;break;}
            if ($id===''&&count($rows)>=self::LIMIT) throw new \DomainException('The catalogue is full. Edit an existing entry.');
            if ($id!==''&&$existing===null) throw new \DomainException('This fee record no longer exists.');
            $record=self::normalize($input,$id===''?'fee-'.bin2hex(random_bytes(8)):$id);
            if ($existing===null) $rows[]=$record; else $rows[$existing]=$record;
            $patch['consultation_fees']=$rows;
        } else throw new \InvalidArgumentException('Unknown fee action.');
        $this->store->upsertIfRevision('settings',$patch,$revision);
        SettingsService::flushCache();
    }
}
