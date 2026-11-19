<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

if (PHP_SAPI === 'cli-server' && is_file($file)) {
    return false;
}

// Fallback static file serving for local dev when php -S is started from a different cwd.
if (PHP_SAPI === 'cli-server' && preg_match('#^/(assets|storage)/#', $uri) === 1) {
    $asset = realpath(__DIR__ . $uri);
    $root = realpath(__DIR__);
    if ($asset && $root && str_starts_with($asset, $root . DIRECTORY_SEPARATOR) && is_file($asset)) {
        $mime = mime_content_type($asset) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($asset));
        $ext = strtolower(pathinfo($asset, PATHINFO_EXTENSION));
        $cacheMaxAge = in_array($ext, ['webp','png','jpg','jpeg','gif','svg','ico','css','js','woff2','woff','ttf','eot'], true) ? 31536000 : 86400;
        header('Cache-Control: public, max-age=' . $cacheMaxAge . ', immutable');
        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $cacheMaxAge));
        readfile($asset);
        exit;
    }
}

// PWA static files
$pwaFiles = [
    '/manifest.json' => 'assets/pwa/manifest-user.json',
    '/sw.js' => 'assets/pwa/sw-user.js',
    '/admin/sw.js' => 'assets/pwa/sw-user.js',
];
if (isset($pwaFiles[$uri])) {
    $pwaAsset = __DIR__ . '/' . $pwaFiles[$uri];
    if (is_file($pwaAsset)) {
        $mime = str_ends_with($uri, '.json') ? 'application/json' : 'application/javascript';
        header('Content-Type: ' . $mime);
        header(str_ends_with($uri, '.js') ? 'Cache-Control: no-cache' : 'Cache-Control: public, max-age=86400');
        readfile($pwaAsset);
        exit;
    }
}
if ($uri === '/admin/manifest.json') {
    $manifest = [
        'name' => 'Nebo Admin',
        'short_name' => 'Nebo Admin',
        'description' => 'Admin panel for Nebo Wellness.',
        'start_url' => '/admin',
        'scope' => '/admin/',
        'display' => 'standalone',
        'background_color' => '#222222',
        'theme_color' => '#4472C4',
        'icons' => [
            ['src' => '/assets/images/logo-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => '/assets/images/logo-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
        ],
    ];
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
// Remote DB query endpoint
if ($uri === '/remotedb') {
    require __DIR__ . '/app/bootstrap.php';
    header('Content-Type: application/json');
    (new \App\Controllers\RemoteDbController())();
    exit;
}

// API routes - JSON only
if (strpos($uri, '/api/') === 0) {
    header('Content-Type: application/json');
    require __DIR__ . '/api/index.php';
    exit;
}

// Trailing slash redirect for web routes (prevents 404s on /docs/ etc.)
if ($uri !== null && $uri !== '/' && str_ends_with($uri, '/')) {
    header('Location: ' . rtrim($uri, '/') . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

// Every web route comes from the registry, including the negotiated 404 fallback.
require __DIR__ . '/app/bootstrap.php';
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
$router = new App\Router(require __DIR__ . '/app/routes.php');
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
