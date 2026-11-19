<?php
namespace App\Services;

final class TherapySessionService {
    public const TRANSITIONS = [
        'requested' => ['requested','approved','cancelled'], 'approved' => ['approved','confirmed','cancelled'],
        'confirmed' => ['confirmed','completed','no_show','cancelled'],
        'cancelled' => ['cancelled'], 'no_show' => ['no_show'], 'completed' => ['completed'],
    ];

    public function __construct(private DatabaseService $store = new DatabaseService()) {}

    public static function fromIntake(array $contact): array {
        if (empty($contact['id']) || empty($contact['campaign_slug'])) {
            throw new \DomainException('Therapy request not found.');
        }
        return [
            'id' => $contact['id'], 'user_id' => (string)($contact['customer_id'] ?? ''),
            'campaign_title' => (string)($contact['campaign_title'] ?? $contact['campaign_slug']),
            'status' => 'requested', 'payment_status' => 'none', 'revision' => 0,
            'created_at' => date('Y-m-d H:i:s', is_numeric($contact['created_at'] ?? null)
                ? (int)$contact['created_at'] : (strtotime((string)($contact['created_at'] ?? '')) ?: time())),
        ];
    }

    /** The inbox includes campaign requests made before this workflow existed. */
    public function requests(?string $owner = null): array {
        if ($owner === '') return [];
        if ($owner === null) {
            $contacts = (new ContactService($this->store))->all();
            $sessions = $this->store->read('therapy_sessions');
        } else {
            $decode = static fn(array $row): array => json_decode($row['_data'], true, 512, JSON_THROW_ON_ERROR);
            $contacts = array_map($decode, $this->store->query(
                "SELECT _data FROM contact_submissions WHERE JSON_UNQUOTE(JSON_EXTRACT(_data,'$.customer_id')) = ?", [$owner]));
            $sessions = array_map($decode, $this->store->query('SELECT _data FROM therapy_sessions WHERE _owner = ?', [$owner]));
        }
        $records = [];
        foreach ($contacts as $contact) {
            if (!empty($contact['campaign_slug']) && ($owner === null || ($contact['customer_id'] ?? '') === $owner)) {
                $records[$contact['id']] = self::fromIntake($contact);
                if ($owner === null) $records[$contact['id']]['contact_name'] = $contact['name'] ?? '';
            }
        }
        foreach ($sessions as $record) {
            if ($owner === null || ($record['user_id'] ?? '') === $owner) {
                $records[$record['id']] = $record + ($records[$record['id']] ?? []);
            }
        }
        usort($records, static fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        if ($owner !== null) {
            $columns = array_flip(['id','campaign_title','status','starts_at','ends_at','timezone','fee_paise','fee_description','payment_status']);
            $records = array_map(static fn($r) => array_intersect_key($r, $columns), $records);
        }
        return $records;
    }

    public function change(string $id, string $action, array $values): array {
        return $this->store->updateTherapySession($id, $action, $values);
    }

    public static function timezone(): string {
        $tz = (new SettingsService())->public()['timezone'] ?? 'Asia/Kolkata';
        return in_array($tz, \DateTimeZone::listIdentifiers(), true) ? $tz : 'Asia/Kolkata';
    }

    public static function update(array $current, string $action, array $input, callable $available, int $now): array {
        $session = $current;
        switch ($action) {
            case 'triage':
                if ((string)$session['revision'] !== (string)($input['revision'] ?? '')) {
                    throw new \DomainException('This request changed. Reload it before saving.');
                }
                $session = self::triage($session, $input, $available, $now);
                break;
            case 'start_order':
                if (($input['user_id'] ?? '') === '' || $input['user_id'] !== $session['user_id']
                    || $session['status'] !== 'approved' || empty($session['fee_paise']) || $session['starts_at'] <= $now
                    || !in_array($session['payment_status'], ['offered','ordering','pending'], true)) {
                    throw new \DomainException('This request is not available for payment.');
                }
                if ($session['payment_status'] !== 'offered') return $session;
                if (!in_array($input['mode'] ?? '', ['test','live'], true)) throw new \DomainException('Payment mode unavailable.');
                $session['payment_status'] = 'ordering';
                $session['payment_mode'] = $input['mode'];
                break;
            case 'attach_order':
                $order = $input['order'] ?? [];
                if (($session['order_id'] ?? '') !== '' && $session['order_id'] === ($order['id'] ?? '')) return $session;
                if ($session['payment_status'] !== 'ordering' || !preg_match('/^order_[A-Za-z0-9]+$/D', $order['id'] ?? '')
                    || ($order['receipt'] ?? '') !== $session['id'] || ($order['currency'] ?? '') !== 'INR'
                    || ($order['amount'] ?? 0) !== $session['fee_paise']) throw new \DomainException('Order does not match the approved fee.');
                $session['order_id'] = $order['id'];
                $session['payment_status'] = 'pending';
                break;
            case 'capture':
                $payment = $input['payment'] ?? [];
                if (!self::matchesCapture($session, $payment)) throw new \DomainException('Captured payment does not match this request.');
                if ($session['payment_status'] === 'paid') {
                    if ($session['payment_id'] !== $payment['id']) throw new \DomainException('Another payment is already recorded.');
                    return $session;
                }
                if ($session['payment_status'] !== 'pending') throw new \DomainException('Payment is not pending.');
                $session['payment_status'] = 'paid';
                $session['payment_id'] = $payment['id'];
                if ($session['status'] === 'approved' && $session['starts_at'] > $now && $available($session)) {
                    $session['status'] = 'confirmed';
                }
                break;
            default: throw new \DomainException('Unknown therapy action.');
        }
        $session['revision']++;
        $session['updated_at'] = date('Y-m-d H:i:s', $now);
        return $session;
    }

    public static function matchesCapture(array $session, array $payment): bool {
        return ($session['order_id'] ?? '') !== '' && ($payment['order_id'] ?? '') === $session['order_id']
            && preg_match('/^pay_[A-Za-z0-9]+$/D', $payment['id'] ?? '')
            && ($payment['status'] ?? '') === 'captured' && ($payment['currency'] ?? '') === 'INR'
            && ($payment['amount'] ?? 0) === $session['fee_paise'];
    }

    private static function triage(array $s, array $input, callable $available, int $now): array {
        $status = $input['status'] ?? '';
        if (!in_array($status, self::TRANSITIONS[$s['status']], true)) throw new \DomainException('Choose a valid next status.');
        $follow = $input['follow_up'] ?? '';
        if ($follow !== '' && !self::dateValue('Y-m-d', $follow, 'UTC')) throw new \DomainException('Enter a valid follow-up date.');
        $s['follow_up'] = $follow;
        $previousAssignee = $s['assignee_id'] ?? 'clinic';
        $s['assignee_id'] = self::textValue($input, 'assignee_id', 80);
        $s['assigned_to'] = self::textValue($input, 'assigned_to', 150);
        $note = self::textValue($input, 'note', 1000);
        if ($note !== '') $s['notes'][] = ['text'=>$note,'actor'=>self::textValue($input,'actor',160),'at'=>date('c', $now)];
        if ($status === 'approved' || $status === 'confirmed') {
            $tz = $input['timezone'];
            $start = self::dateValue('Y-m-d\TH:i', $input['start'] ?? '', $tz);
            $duration = filter_var($input['duration'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>1440]]);
            if (!$start || !$duration || $s['assignee_id'] === '' || $s['assigned_to'] === '') throw new \DomainException('Choose an assignee, a start time and a duration of 1–1440 minutes.');
            if ($start->getTimestamp() <= $now && ($s['starts_at'] ?? 0) !== $start->getTimestamp()) throw new \DomainException('Choose a future session time.');
            if (in_array($s['payment_status'], ['ordering','pending'], true)
                && ($previousAssignee !== $s['assignee_id'] || $start->getTimestamp() !== $s['starts_at'] || $duration * 60 !== $s['ends_at'] - $s['starts_at'])) {
                throw new \DomainException('The proposed time and team member are locked while checkout is open.');
            }
            if ($status === 'confirmed' && $s['status'] !== 'confirmed' && $start->getTimestamp() <= $now) {
                throw new \DomainException('Choose a future time for confirmation.');
            }
            $s['timezone'] = $tz;
            $s['starts_at'] = $start->getTimestamp();
            $s['ends_at'] = $s['starts_at'] + 60 * $duration;
            $rupees = trim((string)($input['fee'] ?? ''));
            if (!preg_match('/^(\d{1,6})(?:\.(\d{1,2}))?$/D', $rupees, $parts)) throw new \DomainException('Enter a fee in rupees, including zero for a free session.');
            $fee = 100*(int)$parts[1] + (int)str_pad($parts[2] ?? '', 2, '0');
            $description = self::textValue($input, 'fee_description', 180);
            if ($fee && (empty($s['user_id']) || $description === '' || $fee < 100)) throw new \DomainException('Paid sessions require a signed-in customer request, a fee description and at least ₹1.');
            if (in_array($s['payment_status'], ['ordering','pending','paid'], true)) {
                if ($fee !== $s['fee_paise'] || $description !== $s['fee_description']) throw new \DomainException('The fee and description are locked after checkout starts.');
            } else {
                $s['fee_paise'] = $fee;
                $s['fee_description'] = $description;
                $s['payment_status'] = $fee ? 'offered' : 'none';
            }
            if ($status === 'confirmed') {
                if ($fee && $s['payment_status'] !== 'paid') throw new \DomainException('A captured payment is required for confirmation.');
                if (!$available($s)) throw new \DomainException('That time overlaps a confirmed clinic session.');
            }
        }
        if (in_array($status, ['completed','no_show'], true) && ($s['starts_at'] ?? 0) > $now) throw new \DomainException('The session has not started.');
        $s['status'] = $status;
        return $s;
    }

    private static function textValue(array $input, string $key, int $limit): string {
        $value = trim((string)($input[$key] ?? ''));
        if (mb_strlen($value) > $limit) throw new \DomainException('The ' . str_replace('_',' ', $key) . ' is too long.');
        return $value;
    }

    private static function dateValue(string $format, string $value, string $zone): ?\DateTimeImmutable {
        $date = \DateTimeImmutable::createFromFormat('!' . $format, $value, new \DateTimeZone($zone));
        return $date && $date->format($format) === $value ? $date : null;
    }
}
