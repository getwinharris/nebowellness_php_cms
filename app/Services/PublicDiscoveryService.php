<?php
namespace App\Services;

final class PublicDiscoveryService {
    /** HTML remains the default for browsers and wildcard-only Accept headers. */
    public static function prefersMarkdown(string $accept): bool {
        $qualities = [];
        foreach (explode(',', strtolower($accept)) as $range) {
            $parts = array_map('trim', explode(';', $range));
            $type = array_shift($parts);
            $quality = 1.0;
            foreach ($parts as $parameter) {
                if (str_starts_with($parameter, 'q=')) {
                    $value = substr($parameter, 2);
                    $quality = preg_match('/^(?:0(?:\.\d{0,3})?|1(?:\.0{0,3})?)$/D', $value) ? (float)$value : 0.0;
                }
            }
            $qualities[$type] = max($qualities[$type] ?? 0.0, $quality);
        }
        $markdown = $qualities['text/markdown'] ?? 0.0;
        $html = $qualities['text/html'] ?? $qualities['text/*'] ?? $qualities['*/*'] ?? 0.0;
        return $markdown > 0 && ($markdown > $html || ($markdown === $html && !isset($qualities['text/html'])));
    }

    public static function sendHome(array $modules): void {
        header('Content-Type: text/markdown; charset=utf-8');
        $links = ['Our Programs' => '/#programs', 'Our Focus Areas' => '/#focus-areas', 'About the clinic' => '/about', 'Contact the clinic' => '/contact'];
        if ($modules['campaigns']) $links['Therapy services'] = '/campaigns';
        if ($modules['blog']) $links['Wellness journal'] = '/blog';
        if ($modules['shop']) $links['Wellness shop'] = '/shop';
        $links['Public sitemap'] = '/sitemap.xml';
        $links['Public discovery API (OpenAPI)'] = '/openapi.json';
        echo "# Nebo Lifestyle Clinic\n\nNaturopathy, functional medicine, and maternal and fertility wellness in Kanyakumari, Tamil Nadu. Nebo offers personalised care for gut health, metabolic diseases, and sustainable lifestyle transformation.\n\n";
        foreach ($links as $label => $path) echo '- [' . $label . '](' . SeoService::publicUrl($path) . ")\n";
    }
}
