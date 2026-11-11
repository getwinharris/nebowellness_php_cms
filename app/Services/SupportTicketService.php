<?php
namespace App\Services;

final class SupportTicketService {
    public function __construct(private DatabaseService $store = new DatabaseService()) {}

    public function all(): array {
        return $this->store->read('support_tickets');
    }

    public function forUser(string $email): array {
        if (trim($email) === '') return [];
        return array_values(array_filter($this->all(), fn($t) => strcasecmp((string)($t['customer_email'] ?? ''), $email) === 0));
    }

    public function track(string $email, string $reference): ?array {
        if (trim($email) === '' || !preg_match('/^(?:[a-f0-9]{16}|[a-f0-9]{32})$/D', $reference)) return null;
        return self::visibleStatus($this->store->find('support_tickets', $reference), $email);
    }

    public static function visibleStatus(?array $ticket, string $email): ?array {
        if ($ticket === null || trim($email) === '' || strcasecmp((string)($ticket['customer_email'] ?? ''), $email) !== 0) return null;
        return array_intersect_key($ticket, array_flip(['id', 'status', 'reply']));
    }

    public function create(string $customerEmail, string $message, string $context = '', ?string $requestId = null): array {
        $id = $requestId === null ? bin2hex(random_bytes(8)) : substr(hash('sha256', strtolower($customerEmail) . ':' . $requestId), 0, 32);
        if ($requestId !== null && ($existing = $this->track($customerEmail, $id)) !== null) return $existing;
        $ticket = [
            'id' => $id,
            'customer_email' => $customerEmail,
            'message' => $message,
            'context' => $context,
            'status' => 'open',
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ];
        return $this->store->upsert('support_tickets', $ticket);
    }

    public function reply(string $ticketId, string $reply): ?array {
        $tickets = $this->all();
        foreach ($tickets as &$t) {
            if (($t['id'] ?? '') === $ticketId) {
                $t['reply'] = $reply;
                $t['status'] = 'answered';
                $t['updated_at'] = date('c');
                $this->store->upsert('support_tickets', $t);
                (new AuditLogService())->record('save', 'support_tickets', $ticketId, ['action' => 'admin_reply']);
                return $t;
            }
        }
        return null;
    }

    public function close(string $ticketId): bool {
        $tickets = $this->all();
        foreach ($tickets as &$t) {
            if (($t['id'] ?? '') === $ticketId) {
                $t['status'] = 'closed';
                $t['updated_at'] = date('c');
                $this->store->upsert('support_tickets', $t);
                (new AuditLogService())->record('save', 'support_tickets', $ticketId, ['action' => 'close']);
                return true;
            }
        }
        return false;
    }
}
