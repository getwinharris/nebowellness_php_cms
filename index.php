<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Match production's developer-only REA boundary in the local PHP server.
if (preg_match('#^/(?:\.agents|cli|bapXcms|content)(?:/|$)#i',rawurldecode((string)$uri))) {
    http_response_code(403);
    exit;
}
$decodedPath=rawurldecode((string)$uri);
if (preg_match('#^/assets/images/brand/#i',$decodedPath) &&
    !preg_match('#^/assets/images/brand/[A-Za-z0-9_-]+\.(?:png|jpe?g|ico|svg|webp|gif)$#iD',$decodedPath)) {
    http_response_code(403);
    exit;
}
$file = __DIR__ . $uri;
if (PHP_SAPI === 'cli-server') {
    $resolved=realpath(__DIR__.$decodedPath);
    $privateRoot=realpath(__DIR__.'/content');
    if ($resolved!==false && $privateRoot!==false && ($resolved===$privateRoot||str_starts_with($resolved,$privateRoot.DIRECTORY_SEPARATOR))) {
        http_response_code(403); exit;
    }
}

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
