<?php
namespace App\Controllers;
use App\Services\{AuthService,OrderService,ReviewService,SettingsService};
final class AccountController extends BaseController {
    public function __construct() {
        (new AuthService())->requireUser();
        $this->seoKey = 'account';
    }

    // A customer's order history is not a shopping flow. These pages must stay
    // reachable after Ecommerce is switched off — the order and invoice are the only
    // record of a completed purchase, and login lands here. New shopping flows
    // (shop, cart, checkout) remain gated in PublicController/CommerceController.
    public function dashboard(): void {
        $this->redirect(($_SESSION['user']['role'] ?? '') === 'customer' ? '/account/therapy' : '/admin');
    }
    public function legacyOrders(): void { $this->redirect('/account/dashboard/orders'); }
    public function legacyBookings(): void { $this->redirect('/account/therapy'); }

    public function orders(): void {
        $userEmail = $_SESSION['user']['email'] ?? '';
        $orders = (new OrderService())->all();
        if ($userEmail !== '') {
            $orders = array_values(array_filter($orders, fn($order) => ($order['customer_email'] ?? '') === $userEmail));
        } else {
            $orders = [];
        }
        $this->render('account/orders', compact('orders'));
    }
    public function bookings(): void { $this->redirect('/account/therapy'); }

    public function install(): void {
        $this->render('account/install');
    }

    /**
     * One order, in full.
     *
     * My Orders was a seven-column table with no way into an individual order, so a
     * customer could see a status but never the items, the address and the tracking
     * together. It doubles as the thank-you screen: a verified payment lands here with
     * ?placed=1, which is the first time the customer sees what they actually bought.
     */
    public function order(string $orderId): void {
        $order = $this->ownedOrder($orderId);
        if (!$order) { $this->renderNotFound(); return; }
        $justPlaced = false; // Historical detail is not a new payment confirmation.
        $settings = (new SettingsService())->public();
        unset($settings['site_profile']);
        $this->render('account/order', compact('order', 'justPlaced', 'settings'));
    }

    /** The order, only if it belongs to the signed-in customer. */
    private function ownedOrder(string $orderId): ?array {
        $userEmail = $_SESSION['user']['email'] ?? '';
        if ($userEmail === '') return null;
        foreach ((new OrderService())->all() as $o) {
            if (($o['id'] ?? '') === $orderId && ($o['customer_email'] ?? '') === $userEmail) return $o;
        }
        return null;
    }

    public function invoice(string $orderId): void {
        $userEmail = $_SESSION['user']['email'] ?? '';
        if ($userEmail === '') { http_response_code(404); echo 'Invoice not found.'; return; }
        $orders = (new OrderService())->all();
        $order = null;
        foreach ($orders as $o) {
            if (($o['id'] ?? '') === $orderId && ($o['customer_email'] ?? '') === $userEmail) {
                $order = $o; break;
            }
        }
        if (!$order) { http_response_code(404); echo 'Invoice not found.'; return; }
        $settings = (new SettingsService())->public();
        unset($settings['site_profile']);
        $this->render('account/invoice', compact('order', 'settings'));
    }

}
