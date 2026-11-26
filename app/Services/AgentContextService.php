<?php
namespace App\Services;

final class AgentContextService {
    public function __construct(private DatabaseService $store = new DatabaseService(), private SchemaService $schema = new SchemaService()) {}

    /**
     * The authenticated actor and question intent come only from the support
     * request. No model-controlled identity, address or raw account object.
     */
    public function supportContext(?array $user,bool $accountQuestion,array $sessionCart): array {
        $modules=(new SettingsService($this->store))->strictModules();
        $shop=!empty($modules['shop']);
        $email=is_string($user['email']??null)?trim($user['email']):'';
        $signedIn=filter_var($email,FILTER_VALIDATE_EMAIL)!==false;
        $customer=$signedIn&&($user['role']??null)==='customer';
        $accountAccess=$shop&&$customer&&$accountQuestion;
        $context=['signed_in'=>$signedIn,'account_access'=>$accountAccess,'shop_enabled'=>$shop,
            'orders'=>$accountAccess?$this->sessionOwnedOrders($email):[],
            'cart'=>[],'articles'=>[],'site'=>$this->siteContext($modules,$customer)];
        if ($shop) foreach (array_slice(array_values($sessionCart),0,20) as $item) {
            if (!is_array($item)) throw new \DomainException('Invalid cart item.');
            $slug=self::slug($item['slug']??null);
            $qty=filter_var($item['qty']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>10000]]);
            if ($qty===false) throw new \DomainException('Invalid cart quantity.');
            $context['cart'][]=['slug'=>$slug,'name'=>self::text($item['name']??'',200),'qty'=>$qty];
        }
        if (!empty($modules['blog'])) foreach (array_slice((new BlogService())->all(false,$modules),0,12) as $post) {
            $context['articles'][]=['title'=>self::text($post['title']??null,200,true),'url'=>'/blog/'.self::slug($post['slug']??null)];
        }
        $settings=$this->store->find('settings',SettingsService::SETTINGS_ID)??[];
        $context['contact_channels']=ClinicContactService::channelsFromSettings($settings);
        // Shop policy inputs are a separate, explicit projection of canonical settings.
        if ($shop) {
            $safe=[];
            foreach (['currency'=>3,'timezone'=>80,'shipping_mode'=>40] as $key=>$limit) {
                if (isset($settings[$key])) $safe[$key]=self::text($settings[$key],$limit,true);
            }
            if (isset($settings['flat_rate'])) $safe['flat_rate_minor']=OwnerAbilityService::minor($settings['flat_rate']);
            if ($safe!==[]) $context['settings']=$safe;
        }
        return $context;
    }

    /** Bounded aggregate summary for the authenticated owner; never includes customer identities. */
    public function forAdmin(bool $shopEnabled): string {
        return self::adminSummary(
            $shopEnabled,
            $this->store->read('users'),
            $shopEnabled ? $this->store->read('orders') : [],
            $shopEnabled ? $this->store->read('products') : [],
            count($this->store->read('consultants')),
            count($this->store->read('appointments')),
            count(array_filter($this->store->read('support_tickets'),static fn(array $row):bool=>trim((string)($row['message']??''))!==''))
        );
    }

    public static function adminSummary(bool $shopEnabled, array $users, array $orders, array $products, int $consultantCount, int $appointmentCount, int $ticketCount): string {
            if (!$shopEnabled) { $orders = []; $products = []; }
            $userCount = count($users);
            $orderCount = count($orders);
            $productCount = count($products);
            $revenueOrders = array_filter($orders, fn($o) => !in_array((string)($o['status'] ?? 'unknown'), ['cancelled', 'refunded'], true));
            $totalRevenue = array_sum(array_column($revenueOrders, 'total'));
            $confirmedOrders = array_filter($orders, fn($o) => in_array(($o['status'] ?? ''), ['confirmed', 'processing', 'packed', 'shipped', 'delivered'], true));
            $pendingOrders = array_filter($orders, fn($o) => ($o['status'] ?? '') === 'pending');
            $confirmedRevenue = array_sum(array_column($confirmedOrders, 'total'));
            $pendingRevenue = array_sum(array_column($pendingOrders, 'total'));
            $avgOrderValue = count($revenueOrders) > 0 ? $totalRevenue / count($revenueOrders) : 0;
            // Per-product sales. "Which products sell best?" could not be answered before,
            // because only a product count reached the model.
            $unitsBySlug = [];
            $revenueBySlug = [];
            $productNames = [];
            foreach ($confirmedOrders as $o) {
                foreach (($o['items'] ?? []) as $item) {
                    $key = (string)($item['slug'] ?? $item['name'] ?? '');
                    if ($key === '') continue;
                    $productNames[$key] = mb_substr((string)($item['name'] ?? $key), 0, 120);
                    $qty = (int)($item['qty'] ?? 1);
                    $unitsBySlug[$key] = ($unitsBySlug[$key] ?? 0) + $qty;
                    $revenueBySlug[$key] = ($revenueBySlug[$key] ?? 0) + (float)($item['line_total'] ?? 0);
                }
            }
            arsort($unitsBySlug);
            $topProductsStr = '';
            foreach (array_slice($unitsBySlug, 0, 5, true) as $slug => $units) {
                $topProductsStr .= "\n  - {$productNames[$slug]}: {$units} sold, ₹" . number_format($revenueBySlug[$slug] ?? 0, 2);
            }
            if ($topProductsStr === '') $topProductsStr = "\n  - no confirmed sales yet";

            $commerceContext = $shopEnabled
                ? "- Total orders: {$orderCount} (confirmed or fulfilled: " . count($confirmedOrders) . ", pending: " . count($pendingOrders) . ")\n"
                    . "- Products: {$productCount}\n"
                    . "- Total revenue: ₹" . number_format($totalRevenue, 2) . "\n"
                    . "- Confirmed or fulfilled revenue: ₹" . number_format($confirmedRevenue, 2) . "\n"
                    . "- Pending revenue (unconfirmed): ₹" . number_format($pendingRevenue, 2) . "\n"
                    . "- Average order value: ₹" . number_format($avgOrderValue, 2) . "\n"
                    . "- Top 5 products by units sold:" . $topProductsStr
                : "- Ecommerce is disabled. Do not offer or discuss products, orders, revenue, coupons, shipping, or checkout.";
            return "You are the admin AI assistant. You have access to the aggregate site data below.\n\n"
                . "Site data:\n"
                . "- Total users: {$userCount}\n"
                . "- Consultants: {$consultantCount}\n"
                . "- Appointments: {$appointmentCount}\n"
                . "- Support tickets: {$ticketCount}\n"
                . $commerceContext
                . "\n\nUse this data to answer. Never repeat it back unless asked for it. "
                . "Customer identities are not included in this summary.";
    }

    private static function text(mixed $value,int $max,bool $required=false): string {
        if ($value===null&&!$required) return '';
        if (!is_string($value)||!mb_check_encoding($value,'UTF-8')||
            ($required&&trim($value)==='')||preg_match('/[\\x00-\\x08\\x0b\\x0c\\x0e-\\x1f\\x7f]/u',$value)) {
            throw new \DomainException('Invalid context source field.');
        }
        return mb_substr($value,0,$max);
    }

    private static function slug(mixed $value): string {
        if (!is_string($value)||strlen($value)>120||!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$value)) throw new \DomainException('Invalid context source slug.');
        return $value;
    }

    private static function textList(mixed $value): array {
        if (!is_array($value)||!array_is_list($value)) throw new \DomainException('Invalid public list.');
        return array_map(static fn($item)=>self::text($item,160),array_slice($value,0,6));
    }

    private static function specifications(mixed $value): array {
        if (!is_array($value)) throw new \DomainException('Invalid public specifications.');
        $out=[];
        foreach (array_slice($value,0,6,true) as $key=>$item) {
            if (!is_string($key)) throw new \DomainException('Invalid public specification name.');
            $out[]=['name'=>self::text($key,100,true),'value'=>self::text($item,200)];
        }
        return $out;
    }

    private function sessionOwnedOrders(string $email): array {
        $fields=array_flip($this->schema->agentContextFields('orders'));
        foreach (['id','status','total'] as $required) if (!isset($fields[$required])) throw new \DomainException('Order projection is unavailable.');
        $out=[];
        foreach ($this->store->read('orders') as $row) {
            if (!is_string($row['customer_email']??null)||strcasecmp(trim($row['customer_email']),$email)!==0) continue;
            $safe=array_intersect_key($row,$fields);
            $status=$safe['status']??null;
            $out[]=['id'=>self::text($safe['id']??null,120,true),
                'status'=>is_string($status)&&in_array($status,OwnerAbilityService::ORDER_STATES,true)?$status:'other',
                'total_minor'=>OwnerAbilityService::orderMinor($safe+['currency'=>$row['currency']??'INR']),
                'currency'=>'INR',
                'shipped_at'=>self::text($safe['shipped_at']??null,80),
                'invoice_number'=>self::text($safe['invoice_number']??null,120),
                'invoice_date'=>self::text($safe['invoice_date']??null,80)];
            if (count($out)>=10) break;
        }
        return $out;
    }

    private function siteContext(array $modules,bool $customer): array {
        $campaigns=[];
        if (!empty($modules['campaigns'])) foreach (array_slice((new CampaignPageService($this->store))->published(),0,12) as $page) {
            $campaigns[]=['title'=>self::text($page['title']??null,200,true),
                'summary'=>self::text($page['summary']??null,400),'url'=>'/campaigns/'.self::slug($page['slug']??null)];
        }
        $products=[];
        if (!empty($modules['shop'])) foreach (array_slice((new ProductService($this->store))->visible(),0,10) as $item) {
            $slug=self::slug($item['slug']??null);
            $products[]=[
                'name'=>self::text($item['name']??null,200,true),'slug'=>$slug,'url'=>'/product/'.$slug,
                'price_minor'=>OwnerAbilityService::minor($item['offer_price']??$item['price']??null),'currency'=>'INR',
                'stock_status'=>self::text($item['stock_status']??null,80),
                'description' => strip_tags(self::text($item['description']??null,600)),
                'highlights' => self::textList($item['highlights']??[]),
                'description_points' => self::textList($item['description_points']??[]),
                'specifications' => self::specifications($item['specifications']??[]),
            ];
        }
        $pages=['contact'=>'/contact'];
        if (!empty($modules['shop'])) {
            $pages+=['shop'=>'/shop','cart'=>'/cart','checkout'=>'/checkout'];
            if ($customer) $pages['orders']='/account/dashboard/orders';
        }
        if (!empty($modules['campaigns'])) $pages['campaigns']='/campaigns';
        if (!empty($modules['blog'])) $pages['blog']='/blog';
        if ($customer) $pages['therapy requests']='/account/therapy';
        return ['pages'=>$pages,'campaigns'=>$campaigns,
            'programmes'=>array_map(static fn(array $row):array=>array_intersect_key($row,array_flip(['title','duration','url'])),
                (new ClinicKnowledgeService($this->store))->programmes()),
            'products'=>$products,'support_scope'=>'Use only permitted public and session-scoped customer tools; no admin data or other users.'];
    }
}
