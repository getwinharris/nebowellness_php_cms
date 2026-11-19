<?php
$text = ['schema' => ['type' => 'string']];
echo json_encode([
    'openapi' => '3.1.0',
    'info' => ['title' => 'Nebo Lifestyle Clinic public discovery', 'version' => '1.0.0'],
    'servers' => [['url' => \App\Services\SeoService::publicUrl('/')]],
    'paths' => [
        '/' => ['get' => [
            'operationId' => 'getClinicOverview',
            'summary' => 'Read the clinic overview and links to available public services',
            'responses' => ['200' => ['description' => 'HTML by default. Request text/markdown with the Accept header for Markdown.', 'content' => ['text/markdown' => $text, 'text/html' => $text]]],
        ]],
        '/sitemap.xml' => ['get' => [
            'operationId' => 'getPublicSitemap',
            'summary' => 'Find published pages and enabled services',
            'responses' => ['200' => ['description' => 'XML sitemap', 'content' => ['application/xml' => $text]], '503' => ['description' => 'Temporary error reading public content']],
        ]],
    ],
], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
