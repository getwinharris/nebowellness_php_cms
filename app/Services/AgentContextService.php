<?php
namespace App\Services;

final class AgentContextService {
    public function __construct(private DatabaseService $store = new DatabaseService(), private SchemaService $schema = new SchemaService()) {}

    public function forUserEmail(string $email): array {
        $email = strtolower(trim($email));
        if ($email === '') return ['user'=>null, 'orders'=>[], 'settings'=>$this->publicSettings(), 'site'=>$this->siteContext()];
        return [
            'user' => $this->firstOwned('users', 'email', $email),
            'orders' => $this->owned('orders', 'customer_email', $email),
            'settings' => $this->publicSettings(),
            'site' => $this->siteContext(),
        ];
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
            count($this->store->read('support_tickets'))
        );
    }

    public static function adminSummary(bool $shopEnabled, array $users, array $orders, array $products, int $consultantCount, int $appointmentCount, int $ticketCount): string {
            if (!$shopEnabled) { $orders = []; $products = []; }
            $userCount = count($users);
            $orderCount = count($orders);
            $productCount = count($products);
            $totalRevenue = array_sum(array_column($orders, 'total'));
            $confirmedOrders = array_filter($orders, fn($o) => ($o['status'] ?? '') === 'confirmed');
            $pendingOrders = array_filter($orders, fn($o) => ($o['status'] ?? '') === 'pending');
            $confirmedRevenue = array_sum(array_column($confirmedOrders, 'total'));
            $pendingRevenue = array_sum(array_column($pendingOrders, 'total'));
            $avgOrderValue = $orderCount > 0 ? $totalRevenue / $orderCount : 0;
            // Per-product sales. "Which products sell best?" could not be answered before,
            // because only a product count reached the model.
            $unitsBySlug = [];
            $revenueBySlug = [];
            foreach ($confirmedOrders as $o) {
                foreach (($o['items'] ?? []) as $item) {
                    $key = (string)($item['name'] ?? $item['slug'] ?? '');
                    if ($key === '') continue;
                    $qty = (int)($item['qty'] ?? 1);
                    $unitsBySlug[$key] = ($unitsBySlug[$key] ?? 0) + $qty;
                    $revenueBySlug[$key] = ($revenueBySlug[$key] ?? 0) + (float)($item['line_total'] ?? 0);
                }
            }
            arsort($unitsBySlug);
            $topProductsStr = '';
            foreach (array_slice($unitsBySlug, 0, 5, true) as $name => $units) {
                $topProductsStr .= "\n  - {$name}: {$units} sold, ₹" . number_format($revenueBySlug[$name] ?? 0, 2);
            }
            if ($topProductsStr === '') $topProductsStr = "\n  - no confirmed sales yet";

            $commerceContext = $shopEnabled
                ? "- Total orders: {$orderCount} (confirmed: " . count($confirmedOrders) . ", pending: " . count($pendingOrders) . ")\n"
                    . "- Products: {$productCount}\n"
                    . "- Total revenue: ₹" . number_format($totalRevenue, 2) . "\n"
                    . "- Confirmed revenue: ₹" . number_format($confirmedRevenue, 2) . "\n"
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

    private function owned(string $collection, string $field, string $email): array {
        $records = array_values(array_filter($this->store->read($collection), fn($item) => strtolower((string)($item[$field] ?? '')) === $email));
        $fields = $this->schema->agentContextFields($collection);
        return $fields ? array_map(fn($item) => array_intersect_key($item, array_flip($fields)), $records) : $records;
    }

    private function firstOwned(string $collection, string $field, string $email): ?array {
        $records = $this->owned($collection, $field, $email);
        return $records[0] ?? null;
    }

    private function publicSettings(): array {
        $settings = $this->store->read('settings')[0] ?? [];
        return array_intersect_key($settings, array_flip(['currency', 'timezone', 'shipping_mode', 'flat_rate']));
    }

    private function siteContext(): array {
        $modules = (new SettingsService($this->store))->modules();
        $campaigns = empty($modules['campaigns']) ? [] : array_map(static fn(array $page): array => [
            'title' => $page['title'],
            'summary' => mb_substr((string)($page['summary'] ?? ''), 0, 400),
            'url' => '/campaigns/' . $page['slug'],
        ], array_slice((new CampaignPageService($this->store))->published(), 0, 12));
        $products = empty($modules['shop']) ? [] : array_map(fn($item) => [
            'name' => $item['name'] ?? '',
            'slug' => $item['slug'] ?? '',
            'url' => '/product/' . ($item['slug'] ?? ''),
            'price' => $item['offer_price'] ?? $item['price'] ?? null,
            'stock_status' => $item['stock_status'] ?? '',
            'description' => mb_substr(strip_tags((string)($item['description'] ?? '')), 0, 2000),
            'highlights' => $item['highlights'] ?? [],
            'description_points' => $item['description_points'] ?? [],
            'specifications' => $item['specifications'] ?? [],
        // Visible only, and priced through the offer window: the support bot must never
        // recommend a product the shopper cannot buy, or quote an offer that has ended.
        ], array_slice((new ProductService($this->store))->visible(), 0, 20));
        $pages = ['contact' => '/contact'];
        if (!empty($modules['shop'])) $pages += ['shop'=>'/shop','cart'=>'/cart','checkout'=>'/checkout','orders'=>'/account/dashboard/orders'];
        if (!empty($modules['campaigns'])) $pages['campaigns'] = '/campaigns';
        if (!empty($modules['blog'])) $pages['blog'] = '/blog';
        return [
            'pages' => $pages,
            'campaigns' => $campaigns,
            'products' => $products,
            'support_scope' => 'Answer only from this JSON context and public site links. Do not access tools, files, admin data, or other users.',
        ];
    }
}
