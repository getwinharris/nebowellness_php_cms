<?php
namespace App\Controllers;

use App\Integrations\Razorpay\RazorpayClient;
use App\Services\{AuditLogService, AuthService, ConsultantService, DatabaseService, PaymentService, SecretService, TherapySessionService};

final class TherapyController extends BaseController {
    private const ACCOUNT = '/account/therapy';
    private const INBOX = '/admin/therapy';
    private TherapySessionService $sessions;
    private DatabaseService $store;

    public function __construct() {
        header('Cache-Control: private, no-store');
        $this->store = new DatabaseService();
        $this->sessions = new TherapySessionService($this->store);
    }

    public function inbox(): void {
        (new AuthService())->requireAdmin();
        $this->layout = 'admin';
        $this->seoKey = 'admin';
        $this->render('admin/therapy', ['title'=>'Therapy requests', 'requests'=>$this->sessions->requests(), 'timezone'=>TherapySessionService::timezone(), 'team'=>$this->team()]);
    }

    public function triage(string $id): void {
        (new AuthService())->requireAdmin();
        $this->validateCsrf();
        $values = array_filter($_POST, 'is_string');
        $values['actor'] = (string)($_SESSION['user']['sub'] ?? 'owner');
        $values['timezone'] = TherapySessionService::timezone();
        try {
            $team = $this->team();
            $assignee = $values['assignee_id'] ?? '';
            if ($assignee !== '' && !isset($team[$assignee])) {
                $current = $this->store->find('therapy_sessions', $id);
                if (($current['assignee_id'] ?? '') !== $assignee) throw new \DomainException('Choose a current clinic team member.');
                $team[$assignee] = $current['assigned_to'];
            }
            $values['assigned_to'] = $team[$assignee] ?? '';
            $result = $this->sessions->change($id, 'triage', $values);
            (new AuditLogService())->record('triage','therapy_sessions',$id, ['status'=>$result['status'], 'revision'=>$result['revision'], 'note_added'=>trim($values['note'] ?? '') !== '']);
            $this->flash('Therapy request updated.', 'success');
        } catch (\DomainException $error) {
            $this->flash($error->getMessage(),'error');
        } catch (\Throwable) {
            $this->flash('Could not save this request. Reload the clinic schedule and try again.','error');
        }
        $this->redirect(self::INBOX . '#request-' . rawurlencode($id), 303);
    }

    public function account(): void {
        $userId = $this->customerId();
        $this->seoKey = 'account';
        $this->render('account/therapy', ['requests'=>$this->sessions->requests($userId)]);
    }

    public function checkout(string $id): void {
        $userId = $this->customerId();
        $this->validateCsrf();
        $secrets = (new SecretService())->all();
        try {
            $request = $this->store->find('therapy_sessions', $id);
            if (!$request || $request['user_id'] !== $userId) $this->renderNotFound();
            $mode = $request['payment_mode'] ?? $secrets['razorpay_mode'];
            $config = require app_path('config/database.php');
            $clinicHost = strtolower((string)parse_url($config['app_url'], PHP_URL_HOST));
            if ($mode === 'test' && in_array($clinicHost, ['nebowellness.com','www.nebowellness.com'], true)) {
                throw new \DomainException('Session checkout is awaiting live payment setup. Please contact the clinic.');
            }
            [$key, $secret] = $this->credentials($secrets, $mode);
            if (empty($secrets['therapy_webhook_' . $mode])) throw new \DomainException('The clinic is still setting up session payments.');
            $request = $this->sessions->change($id, 'start_order', ['user_id'=>$userId, 'mode'=>$mode]);
            if ($request['payment_mode'] !== $mode) throw new \DomainException('The payment mode changed. Reopen checkout from this request.');
            if (empty($request['order_id'])) {
                $client = new RazorpayClient($key, $secret);
                $order = $client->findOrderByReceipt($id) ?? $client->createOrder($request['fee_paise'], $id);
                $request = $this->sessions->change($id, 'attach_order', ['order'=>$order]);
            }
            $this->seoKey = 'account';
            if ($request['status'] !== 'approved') throw new \DomainException('The request has changed. Check the latest session status.');
            $this->render('account/therapy-checkout', compact('request', 'key'));
        } catch (\DomainException $error) {
            $this->flash($error->getMessage(), 'error');
            $this->redirect(self::ACCOUNT, 303);
        } catch (\Throwable) {
            $this->flash('Checkout is temporarily unavailable. Retry this request to recover the same order.', 'error');
            $this->redirect(self::ACCOUNT, 303);
        }
    }

    public function verify(string $id): void {
        $userId = $this->customerId();
        $this->validateCsrf();
        $request = $this->store->find('therapy_sessions', $id);
        if (!$request || $request['user_id'] !== $userId) $this->renderNotFound();
        try {
            [$key, $secret] = $this->credentials((new SecretService())->all(), $request['payment_mode'] ?? '');
            $paymentId = is_string($_POST['razorpay_payment_id'] ?? null) ? $_POST['razorpay_payment_id'] : '';
            $signature = is_string($_POST['razorpay_signature'] ?? null) ? $_POST['razorpay_signature'] : '';
            $orderId = (string)($request['order_id'] ?? '');
            if (!preg_match('/^pay_[A-Za-z0-9]+$/D', $paymentId) || $orderId !== ($_POST['razorpay_order_id'] ?? '')
                || !(new PaymentService($secret))->verifySignature($orderId, $paymentId, $signature)) throw new \DomainException();
            if ($request['payment_status'] === 'paid' && ($request['payment_id'] ?? '') === $paymentId) {
                $this->flash('Payment already received. Your session status is shown below.', 'success');
                $this->redirect(self::ACCOUNT, 303);
            }
            $payment = (new RazorpayClient($key, $secret))->fetchPayment($paymentId);
            if (($payment['id'] ?? '') !== $paymentId) throw new \DomainException();
            $this->sessions->change($id, 'capture', ['payment'=>$payment]);
            $this->flash('Payment received. Your current session status is shown below.', 'success');
        } catch (\Throwable) {
            $this->flash('Payment is awaiting verification. If you were charged, return here for the updated status or contact the clinic before retrying.', 'warning');
        }
        $this->redirect(self::ACCOUNT, 303);
    }

    public function webhook(string $mode): void {
        if (!in_array($mode, ['test','live'], true)) $this->jsonResponse(['error'=>'Unknown mode'],404);
        $raw = (string)file_get_contents('php://input', false, null, 0, 262145);
        $secret = (new SecretService())->all()['therapy_webhook_' . $mode] ?? '';
        if (strlen($raw) > 262144 || $secret === '' || !hash_equals(hash_hmac('sha256', $raw, $secret), $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '')) {
            $this->jsonResponse(['error'=>'Invalid notification'],403);
        }
        $event = json_decode($raw, true);
        if (!is_array($event)) $this->jsonResponse(['error'=>'Invalid event'],400);
        if (($event['event'] ?? '') !== 'payment.captured') $this->jsonResponse(['received'=>true]);
        $payment = $event['payload']['payment']['entity'] ?? [];
        if (!is_array($payment) || !is_string($payment['order_id'] ?? null)) $this->jsonResponse(['error'=>'Missing order'],422);
        try {
            $rows = $this->store->query("SELECT id FROM therapy_sessions WHERE JSON_UNQUOTE(JSON_EXTRACT(_data, '$.order_id')) = ?
                AND JSON_UNQUOTE(JSON_EXTRACT(_data, '$.payment_mode')) = ? LIMIT 1", [$payment['order_id'], $mode]);
            // Other Razorpay orders (including shop purchases) can share the merchant account.
            if ($rows !== []) $this->sessions->change($rows[0]['id'], 'capture', ['payment'=>$payment]);
        } catch (\DomainException) {
            $this->jsonResponse(['error'=>'Payment does not match the approved session'],409);
        } catch (\Throwable) {
            $this->jsonResponse(['error'=>'Retry notification later'],503);
        }
        $this->jsonResponse(['received'=>true]);
    }

    private function team(): array {
        $team = ['clinic'=>'Clinic team'];
        foreach ((new ConsultantService($this->store))->all(true) as $consultant) {
            if (!empty($consultant['id']) && !empty($consultant['name'])) {
                $team['consultant_' . $consultant['id']] = $consultant['name'];
            }
        }
        return $team;
    }

    private function credentials(array $secrets, string $mode): array {
        $prefix = 'razorpay_' . $mode . '_key_';
        if (!in_array($mode, ['test','live'], true) || !str_starts_with((string)($secrets[$prefix . 'id'] ?? ''), 'rzp_' . $mode . '_') || empty($secrets[$prefix . 'secret'])) {
            throw new \DomainException('The clinic is still setting up session payments.');
        }
        return [$secrets[$prefix . 'id'], $secrets[$prefix . 'secret']];
    }

    private function customerId(): string {
        (new AuthService())->requireUser();
        $user = $_SESSION['user'];
        if (($user['role'] ?? '') !== 'customer' || empty($user['sub'])) $this->renderNotFound();
        return (string)$user['sub'];
    }
}
