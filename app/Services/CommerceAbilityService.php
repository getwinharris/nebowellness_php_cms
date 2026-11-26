<?php
declare(strict_types=1);
namespace App\Services;

/** Existing shop read projections; never enable shop or perform checkout operations. */
final class CommerceAbilityService
{
    public function __construct(private DatabaseService $store) {}

    private static function text(mixed $value,int $limit,bool $required=false): string {
        if ($value===null&&!$required) return '';
        if (!is_string($value)||!mb_check_encoding($value,'UTF-8')||mb_strlen($value)>$limit||
            ($required&&trim($value)==='')||preg_match('/[\x00-\x1f\x7f]/u',$value)) throw new \DomainException('Invalid stored commerce text.');
        return $value;
    }

    private static function timestamp(mixed $raw): ?int {
        if ($raw===null||$raw==='') return null;
        if (is_int($raw)||(is_string($raw)&&ctype_digit($raw))) $value=(int)$raw;
        elseif (is_string($raw)&&preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})?$/D',$raw)) {
            try { $date=new \DateTimeImmutable($raw);$errors=\DateTimeImmutable::getLastErrors();
                $value=$errors===false||($errors['warning_count']===0&&$errors['error_count']===0)?$date->getTimestamp():false;
            } catch (\Throwable) { $value=false; }
        }
        else $value=false;
        if ($value===false||$value<0) throw new \DomainException('Invalid stored order date.');
        return $value;
    }

    private static function status(mixed $status): string {
        return is_string($status)&&in_array($status,OwnerAbilityService::ORDER_STATES,true)?$status:'other';
    }

    private static function quantity(mixed $value): int {
        if (is_string($value)&&preg_match('/^[1-9][0-9]{0,8}$/D',$value)) $value=(int)$value;
        if (!is_int($value)||$value<1||$value>999999999) throw new \DomainException('Invalid stored quantity.');
        return $value;
    }

    private static function basicOrder(array $row): array {
        $created=self::timestamp($row['created_at']??null);
        return ['id'=>self::text($row['id']??null,120,true),'status'=>self::status($row['status']??null),
            'total_minor'=>OwnerAbilityService::orderMinor($row),'currency'=>'INR',
            'created_at'=>$created===null?null:date('c',$created)];
    }

    private function orders(): array {
        $rows=(new OrderService($this->store))->all();
        usort($rows,static fn(array $a,array $b):int=>
            ((self::timestamp($b['created_at']??null)??-1)<=>(self::timestamp($a['created_at']??null)??-1))
                ?:strcmp((string)($a['id']??''),(string)($b['id']??'')));
        return $rows;
    }

    public function recentOrders(): array {
        return ['orders'=>array_map(self::basicOrder(...),array_slice($this->orders(),0,10))];
    }

    public function categories(): array {
        $rows=(new CategoryService($this->store))->all();
        $out=[];
        foreach ($rows as $row) {
            $slug=self::text($row['slug']??null,120,true);
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)) throw new \DomainException('Invalid category slug.');
            $out[]=['name'=>self::text($row['name']??null,200,true),'slug'=>$slug];
        }
        usort($out,static fn(array $a,array $b):int=>strcmp($a['slug'],$b['slug']));
        return ['categories'=>array_slice($out,0,15)];
    }

    public static function orderSummary(array $row): array {
        $items=$row['items']??[];
        if (!is_array($items)||!array_is_list($items)) throw new \DomainException('Invalid stored order items.');
        $out=[];
        foreach (array_slice($items,0,50) as $item) {
            if (!is_array($item)) throw new \DomainException('Invalid stored item.');
            $out[]=['name'=>self::text($item['name']??$item['slug']??null,200,true),
                'qty'=>self::quantity($item['qty']??null),
                'line_total_minor'=>isset($item['line_total'])?OwnerAbilityService::minor($item['line_total']):null];
        }
        return self::basicOrder($row)+[
            'courier'=>self::text($row['courier_name']??null,160),
            'tracking_id'=>self::text($row['tracking_id']??null,160),
            'items'=>$out,'item_count'=>count($items),
        ];
    }

    public function findOrder(array $args): array {
        $id=strtolower(trim($args['order_id']??''));
        $email=strtolower(trim($args['customer_email']??''));
        if ($id===''&&$email==='') throw new \InvalidArgumentException('A reference or customer email is required.');
        if ($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Invalid customer email.');
        $out=[];$found=0;
        foreach ($this->orders() as $row) {
            if ($id!==''&&!str_starts_with(strtolower((string)($row['id']??'')),$id)) continue;
            if ($email!==''&&(!is_string($row['customer_email']??null)||strcasecmp($row['customer_email'],$email)!==0)) continue;
            $found++;
            if (count($out)<10) $out[]=self::orderSummary($row);
        }
        return ['found'=>$found,'orders'=>$out];
    }

    public function products(array $args): array {
        $needle=mb_strtolower(trim($args['slug']??''));$out=[];$found=0;
        $rows=(new ProductService($this->store))->all();
        usort($rows,static fn(array $a,array $b):int=>strcmp((string)($a['slug']??''),(string)($b['slug']??'')));
        foreach ($rows as $row) {
            $slug=self::text($row['slug']??null,120,true);
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)) throw new \DomainException('Invalid product slug.');
            $name=self::text($row['name']??null,200,true);
            if ($needle!==''&&!str_contains(mb_strtolower($slug),$needle)&&!str_contains(mb_strtolower($name),$needle)) continue;
            $found++;
            if (count($out)>=10) continue;
            $out[]=['slug'=>$slug,'name'=>$name,'price_minor'=>OwnerAbilityService::minor($row['price']??null),
                'offer_price_minor'=>isset($row['offer_price'])?OwnerAbilityService::minor($row['offer_price']):null,
                'currency'=>'INR','stock_status'=>self::text($row['stock_status']??null,80),
                'hidden_from_shop'=>!empty($row['is_hidden'])];
        }
        return ['found'=>$found,'products'=>$out];
    }

    public function sales(array $args): array {
        $days=$args['days']??30;$now=time();$since=$now-$days*86400;
        $counts=array_fill_keys(OwnerAbilityService::ORDER_STATES,0);$confirmed=0;$pending=0;$best=[];
        foreach ((new OrderService($this->store))->all() as $row) {
            $created=self::timestamp($row['created_at']??null);
            if ($created===null) throw new \DomainException('Order creation date unavailable for period calculation.');
            if ($created<$since||$created>$now) continue;
            $status=self::status($row['status']??null);$counts[$status]++;
            if ($status==='pending') $pending+=OwnerAbilityService::orderMinor($row);
            if (!in_array($status,OwnerAbilityService::CONFIRMED_ORDER_STATES,true)) continue;
            $confirmed+=OwnerAbilityService::orderMinor($row);
            $items=$row['items']??[];
            if (!is_array($items)||!array_is_list($items)) throw new \DomainException('Invalid stored order items.');
            foreach ($items as $item) {
                if (!is_array($item)) throw new \DomainException('Invalid stored item.');
                $name=self::text($item['name']??$item['slug']??null,200,true);
                $key=self::text($item['slug']??$name,200,true);
                $best[$key]??=['name'=>$name,'units'=>0];
                $best[$key]['units']+=self::quantity($item['qty']??null);
            }
        }
        uasort($best,static fn(array $a,array $b):int=>($b['units']<=>$a['units'])?:strcmp($a['name'],$b['name']));
        return ['days'=>$days,'orders'=>array_sum($counts),'orders_by_status'=>$counts,
            'confirmed_order_total_minor'=>$confirmed,'pending_order_total_minor'=>$pending,
            'currency'=>'INR','best_sellers'=>array_values(array_slice($best,0,5)),
            'scope'=>'Rolling stored order totals and units by status; not verified captured-payment revenue.'];
    }

    public function coupon(array $args): array {
        $code=trim($args['code']);
        $email=trim($args['customer_email']??'');
        if ($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Invalid customer email.');
        $coupons=new CouponService($this->store);
        $coupon=$coupons->find($code);
        $base=['found'=>false,'code'=>'','discount_type'=>'','discount_value'=>null,'starts_at'=>'','ends_at'=>'',
            'times_used'=>0,'usable_now'=>null,'why_not'=>'No coupon with that code.',
            'cart_total_minor'=>$args['cart_total_minor']??null,'customer_usage_checked'=>false];
        if ($coupon===null) return $base;
        $discount=$coupon['discount_value']??null;
        if ((!is_int($discount)&&!is_float($discount)&&(!is_string($discount)||!is_numeric($discount)))||
            !is_finite((float)$discount)||(float)$discount<0) throw new \DomainException('Invalid coupon discount.');
        $used=$coupons->timesUsed($code);
        $owned=$email===''?null:$coupons->timesUsed($code,$email);
        $eligibility=$coupons->diagnosticEligibility($coupon,isset($args['cart_total_minor'])?$args['cart_total_minor']/100:null,$used,$owned);
        return array_replace($base,['found'=>true,'code'=>self::text($coupon['code']??null,120,true),
            'discount_type'=>self::text($coupon['discount_type']??null,40,true),'discount_value'=>(float)$discount,
            'starts_at'=>self::text($coupon['starts_at']??null,80),'ends_at'=>self::text($coupon['ends_at']??null,80),
            'times_used'=>$used,'usable_now'=>$eligibility['usable_now'],'why_not'=>$eligibility['why_not'],
            'customer_usage_checked'=>$owned!==null]);
    }
}
