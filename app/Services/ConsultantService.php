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

    /** Optional owner-published professional identifier; never a login credential. */
    public static function registrationNumber(mixed $value): string {
        if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) throw new \InvalidArgumentException('Enter a valid registration number.');
        $value = trim($value);
        if (mb_strlen($value) > 80 || preg_match('/[\x00-\x1f\x7f<>]/u', $value)) throw new \InvalidArgumentException('Use plain text up to 80 characters for the registration number.');
        return $value;
    }

    public function findBySlug(string $slug): ?array {
        foreach ($this->all() as $item) if (($item['slug'] ?? '') === $slug) return $item;
        return null;
    }
}
