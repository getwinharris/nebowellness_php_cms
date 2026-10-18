<?php
namespace App\Services;

final class ContactService {
    private DatabaseService $store;

    public function __construct(?DatabaseService $store = null) {
        $this->store = $store ?? new DatabaseService();
    }

    public function all(): array {
        $items = $this->store->read('contact_submissions');
        usort($items, fn($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
        return $items;
    }

    public function find(string $id): ?array {
        foreach ($this->all() as $item) {
            if (($item['id'] ?? '') === $id) return $item;
        }
        return null;
    }

    public function save(array $data): string {
        $id = $data['id'] ?? uniqid('contact_', true);
        $data['id'] = $id;
        $data['created_at'] = $data['created_at'] ?? time();
        $data['status'] = $data['status'] ?? 'new';
        $previous = $this->store->find('contact_submissions', $id);
        $this->store->upsert('contact_submissions', $data);
        try { (new MailStorageService($this->store))->recordInboxFromContact($data); }
        catch (\Throwable $error) {
            if ($previous !== null) $this->store->upsert('contact_submissions', $previous);
            else $this->store->delete('contact_submissions', $id);
            throw $error;
        }
        return $id;
    }

    public function updateStatus(string $id, string $status): void {
        $item = $this->find($id);
        if ($item) {
            $item['status'] = $status;
            $this->store->upsert('contact_submissions', $item);
        }
    }

    public function delete(string $id): void {
        $this->store->delete('contact_submissions', $id);
    }

    public function count(): int {
        return count($this->all());
    }

    public function unreadCount(): int {
        return count(array_filter($this->all(), fn($item) => ($item['status'] ?? 'new') === 'new'));
    }
}
