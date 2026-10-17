<?php
namespace App\Services;
final class ConsultantService {
    public function __construct(private DatabaseService $store = new DatabaseService()) {}

    public function all(bool $includeHidden = false): array {
        $items = $this->store->read('consultants');
        if (!$includeHidden) $items = array_values(array_filter($items, static fn(array $item): bool => ($item['status'] ?? 'visible') === 'visible'));
        usort($items, static fn(array $a, array $b): int => ((int)($a['display_order'] ?? 999)) <=> ((int)($b['display_order'] ?? 999)));
        return $items;
    }

    public function findBySlug(string $slug): ?array {
        foreach ($this->all() as $item) if (($item['slug'] ?? '') === $slug) return $item;
        return null;
    }
}
