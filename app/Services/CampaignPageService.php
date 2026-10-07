<?php
namespace App\Services;

final class CampaignPageService {
    public function __construct(private DatabaseService $store = new DatabaseService()) {}

    public function published(): array {
        $pages = array_values(array_filter(
            $this->store->read('campaign_pages'),
            static fn(array $page): bool => ($page['status'] ?? 'draft') === 'published'
                && trim((string)($page['slug'] ?? '')) !== ''
                && trim((string)($page['title'] ?? '')) !== ''
        ));
        usort($pages, static fn(array $a, array $b): int => strcmp((string)$a['title'], (string)$b['title']));
        return $pages;
    }

    public function findPublished(string $slug): ?array {
        foreach ($this->published() as $page) {
            if ($page['slug'] === $slug) return $page;
        }
        return null;
    }
}
