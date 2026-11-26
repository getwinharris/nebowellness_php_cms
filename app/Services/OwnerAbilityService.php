<?php
declare(strict_types=1);
namespace App\Services;

/** Owner-only read projections. Actor and module authorization belong to the native registry. */
final class OwnerAbilityService
{
    public const APPOINTMENT_STATES=['requested','queued','accepted','active','completed','declined','cancelled','other'];
    public const TICKET_STATES=['open','answered','closed','other'];
    public const ORDER_STATES=['pending','confirmed','processing','packed','shipped','delivered','cancelled','refunded','other'];
    public const CONFIRMED_ORDER_STATES=['confirmed','processing','packed','shipped','delivered'];
    public const ENQUIRY_TOPICS=[
        'orders'=>['order','ordered','purchase','invoice'],
        'delivery'=>['deliver','delivery','shipping','shipment','shipped','courier','track','tracking','parcel'],
        'payment'=>['payment','paid','card','upi','razorpay','charge'],
        'refund'=>['refund','cancel','cancellation','return','money back'],
        'product'=>['product','pendant','ring','stock','size','item'],
    ];

    public function __construct(private DatabaseService $store) {}

    private static function text(mixed $value,int $limit,bool $required=false): string {
        if ($value===null&&!$required) return '';
        if (!is_string($value)||!mb_check_encoding($value,'UTF-8')||mb_strlen($value)>$limit||
            ($required&&trim($value)==='')||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/u',$value)) throw new \DomainException('Invalid stored text.');
        return $value;
    }

    private static function state(mixed $value,array $states): string {
        return is_string($value)&&in_array($value,$states,true)?$value:'other';
    }

    /** Existing commerce checkout uses INR/paise; reject other currencies rather than add them. */
    public static function orderMinor(array $row): int {
        if (($row['currency']??'INR')!=='INR') throw new \DomainException('Unsupported order currency.');
        return self::minor($row['total']??null);
    }

    public static function minor(mixed $value): int {
        if ((!is_int($value)&&!is_float($value)&&(!is_string($value)||!preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/D',$value)))||
            !is_finite((float)$value)||(float)$value<0||(float)$value>9999999999.99) throw new \DomainException('Invalid monetary amount.');
        $scaled=(float)$value*100;$rounded=round($scaled);
        if (abs($scaled-$rounded)>0.0002) throw new \DomainException('Amount has excess precision.');
        return (int)$rounded;
    }

    public function dashboard(bool $shopEnabled): array {
        $tickets=$this->supportEnquiries([]);
        $out=['registered_users'=>count($this->store->read('users')),
            'consultants'=>count($this->store->read('consultants')),
            'historical_appointments'=>count($this->store->read('appointments')),
            'support_tickets'=>$tickets['total'],'shop_enabled'=>false,
            'scope'=>'Current stored operational counts; not a time-filtered report.'];
        return $out;
    }

    public function appointments(): array {
        return self::summariseAppointments($this->store->read('appointments'));
    }

    public static function summariseAppointments(array $rows): array {
        $counts=array_fill_keys(self::APPOINTMENT_STATES,0);
        foreach ($rows as $row) $counts[self::state($row['status']??null,self::APPOINTMENT_STATES)]++;
        return ['total'=>count($rows),'by_status'=>$counts,'scope'=>'Historical appointments only'];
    }

    public function supportEnquiries(array $args): array {
        $topic=$args['topic']??'';
        $byStatus=array_fill_keys(self::TICKET_STATES,0);
        $byTopic=array_fill_keys(array_keys(self::ENQUIRY_TOPICS),0);
        $total=0;$unanswered=0;
        foreach ((new SupportTicketService($this->store))->all() as $ticket) {
            // Historical id-only rows are not actual enquiries.
            $message=self::text($ticket['message']??null,16000);
            if (trim($message)==='') continue;
            $reply=self::text($ticket['reply']??null,16000);
            $total++;$byStatus[self::state($ticket['status']??'open',self::TICKET_STATES)]++;
            if (trim($reply)==='') $unanswered++;
            $blob=mb_strtolower($message.' '.$reply);
            foreach (self::ENQUIRY_TOPICS as $name=>$words) {
                foreach ($words as $word) if (str_contains($blob,$word)) {$byTopic[$name]++;break;}
            }
        }
        return ['total'=>$total,'by_status'=>$byStatus,'by_topic'=>$byTopic,'awaiting_reply'=>$unanswered,
            'topic_words_used'=>self::ENQUIRY_TOPICS,'topic'=>$topic,
            'matching'=>$topic===''?$total:$byTopic[$topic],
            'message'=>$total===0?'No customer support enquiries have been recorded yet.':''];
    }

    public function campaigns(array $args): array {
        $slug=$args['slug']??'';$out=[];$found=0;
        $pages=$this->store->read('campaign_pages');
        usort($pages,static fn(array $a,array $b):int=>strcmp((string)($a['id']??''),(string)($b['id']??'')));
        foreach ($pages as $page) {
            if ($slug!==''&&($page['slug']??null)!==$slug) continue;
            $key=self::text($page['slug']??null,120,true);
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$key)) throw new \DomainException('Invalid campaign slug.');
            $status=$page['status']??'draft';
            if (!in_array($status,['draft','published'],true)) throw new \DomainException('Invalid campaign status.');
            $found++;
            if (count($out)>=20) continue;
            $out[]=['title'=>self::text($page['title']??null,200,true),'slug'=>$key,'status'=>$status,
                'public_url'=>$status==='published'?'/campaigns/'.$key:null,
                'summary'=>mb_substr(self::text($page['summary']??null,16000),0,300)];
        }
        usort($out,static fn(array $a,array $b):int=>strcmp($a['slug'],$b['slug']));
        return ['found'=>$found,'campaigns'=>$out];
    }

    public function therapyEnquiries(array $args): array {
        return self::summariseTherapyEnquiries($this->store->read('contact_submissions'),$args);
    }

    public static function summariseTherapyEnquiries(array $rows,array $args,?int $now=null): array {
        $now??=time();$days=$args['days']??30;$slug=$args['slug']??'';
        if (!is_int($days)||$days<1||$days>90||!is_string($slug)||
            ($slug!==''&&(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)||strlen($slug)>120))) throw new \InvalidArgumentException('Invalid therapy enquiry filter.');
        $statuses=['new'=>0,'read'=>0,'closed'=>0,'other'=>0];
        foreach ($rows as $row) {
            $campaign=$row['campaign_slug']??'';
            if ($campaign===''||($slug!==''&&$campaign!==$slug)) continue;
            if (!is_string($campaign)) throw new \DomainException('Invalid campaign reference.');
            $raw=$row['created_at']??null;
            if (is_int($raw)||(is_string($raw)&&ctype_digit($raw))) $created=(int)$raw;
            elseif (is_string($raw)&&preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}/',$raw)) $created=strtotime($raw);
            else $created=false;
            if ($created===false||$created<0) throw new \DomainException('An enquiry has an invalid creation date.');
            if ($created<$now-$days*86400||$created>$now) continue;
            $statuses[self::state($row['status']??'new',array_keys($statuses))]++;
        }
        return ['days'=>$days,'request_count'=>array_sum($statuses),'inbox_status'=>$statuses,'admin_url'=>'/admin/contact-submissions'];
    }
}
