<?php
namespace App\Services;

final class MailStorageService {
    public function __construct(private DatabaseService $store = new DatabaseService()) {}

    public function inbox(): array {
        return $this->sorted('mail_inbox');
    }

    public function outbox(): array {
        return $this->sorted('mail_outbox');
    }

    public function recordInboxFromContact(array $contact): array {
        $id = 'in_' . (string)($contact['id'] ?? bin2hex(random_bytes(6)));
        $createdAt = $contact['created_at'] ?? date('c');
        if (is_numeric($createdAt)) $createdAt = date('c', (int)$createdAt);
        // A campaign enquiry's real content is in form_data. The inbox copy used to be
        // the standard message only, so the owner never saw the custom answers unless
        // they read the separate contact-submissions table.
        $body = trim((string)($contact['message'] ?? ''));
        $answers = is_array($contact['form_data'] ?? null) ? $contact['form_data'] : [];
        $answerLines = [];
        foreach ($answers as $answerKey => $answerValue) {
            if (!is_scalar($answerValue)) continue;
            $answerValue = trim((string)$answerValue);
            if ($answerValue === '') continue;
            $answerLines[] = ucwords(str_replace('_', ' ', (string)$answerKey)) . ': ' . $answerValue;
        }
        if ($answerLines !== []) {
            $body = trim($body . "\n\nCampaign answers:\n" . implode("\n", $answerLines));
        }
        return $this->store->upsert('mail_inbox', [
            'id' => $id,
            'from_email' => trim((string)($contact['email'] ?? '')),
            'from_name' => trim((string)($contact['name'] ?? '')),
            'to_email' => 'admin',
            'subject' => trim((string)($contact['subject'] ?? 'Contact request')) ?: 'Contact request',
            'body' => $body,
            'status' => 'unread',
            'source' => 'contact_submissions',
            'source_id' => (string)($contact['id'] ?? ''),
            'created_at' => $createdAt,
        ]);
    }

    public function recordQueuedOutbox(array $mail): array {
        $id = 'out_' . (string)($mail['id'] ?? bin2hex(random_bytes(6)));
        return $this->store->upsert('mail_outbox', [
            'id' => $id,
            'queue_id' => (string)($mail['id'] ?? ''),
            'to_email' => (string)($mail['to'] ?? ''),
            'from_email' => (string)($mail['from'] ?? ''),
            'subject' => (string)($mail['subject'] ?? ''),
            'body' => (string)($mail['html'] ?? ''),
            'status' => (string)($mail['status'] ?? 'pending'),
            'transport' => (string)($mail['transport'] ?? 'queued'),
            'source' => (string)($mail['type'] ?? 'mail_queue'),
            'source_id' => (string)(($mail['meta']['order_id'] ?? '') ?: ($mail['id'] ?? '')),
            'created_at' => (string)($mail['created_at'] ?? date('c')),
        ]);
    }

    public function updateOutboxForQueue(string $queueId, string $status, array $extra = []): void {
        $records = $this->store->read('mail_outbox');
        foreach ($records as &$record) {
            if (($record['queue_id'] ?? '') !== $queueId) continue;
            $record = array_merge($record, $extra, ['status' => $status]);
            break;
        }
        unset($record);
        $this->store->write('mail_outbox', $records);
    }

    private function sorted(string $collection): array {
        $items = $this->store->read($collection);
        usort($items, fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        return $items;
    }
}
