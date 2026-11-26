<?php
/**
 * Local route/API smoke test.
 *
 * Starts a disposable PHP built-in server, checks the core public, account,
 * admin, API, support, and 404 routes, then restores any support-ticket data
 * touched during the test.
 */
require __DIR__ . '/../app/bootstrap.php';

$port = 6200 + random_int(0, 799);
$base = "http://127.0.0.1:{$port}";
$descriptor = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];
$environment = array_merge(getenv() ?: [], ['BAPX_TEST_MODE' => '1']);
$process = proc_open('php -S 127.0.0.1:' . $port . ' index.php', $descriptor, $pipes, app_path(), $environment);
if (!is_resource($process)) {
    fwrite(STDERR, "Unable to start local PHP server.\n");
    exit(1);
}

$failures = [];

try {
    try {
        waitForServer($base);
    } catch (RuntimeException $e) {
        $err = '';
        if (isset($pipes[2]) && is_resource($pipes[2])) {
            stream_set_blocking($pipes[2], false);
            $err = stream_get_contents($pipes[2]);
        }
        throw new RuntimeException($e->getMessage() . ($err ? "\nPHP Server Stderr:\n" . $err : ''));
    }
    foreach ([
        '/' => 200,
        '/shop' => 404,
        '/admin/products' => 404,
        '/admin/coupons' => 404,
        '/admin/shipping' => 404,
        '/admin/orders' => 404,
        '/cli/product-read.php' => 403,
        '/bapXcms' => 403,
        '/assets/images/brand/not-an-image.txt' => 403,
        // Test mode has no records, so a product slug legitimately resolves to nothing.
        // 404 is the correct answer now that a missing product no longer renders an
        // empty page with HTTP 200 (a soft 404 that search engines indexed).
        '/product/karuppasami-dollar' => 404,
        '/cart' => 404,
        '/checkout' => 404,
        '/contact' => 200,
        '/consult' => 302,
        '/consult/shrikanth-purushothaman' => 302,
        '/api/consult' => 404,
        '/temples' => 301,
        '/sri-panchami-spiritual' => 301,
        '/forgot-password' => 200,
        '/reset-password' => 200,
        '/auth/google' => 302,
        '/auth/google/callback' => 302,
        '/logout' => 302,
        '/account/orders' => 302,
        '/account/bookings' => 302,
        '/account/wallet' => 404,
        '/recharge' => 404,
        '/admin' => 302,
        '/admin/settings' => 302,
        '/.agents/REA/catalog.json' => 403,
        '/.agents/REA/reference/wordpress/LICENSE' => 403,
        '/.agents/skills/docs/SKILL.md' => 403,
        '/.agents/map.mmd' => 403,
        '/.agents/index.yaml' => 403,
        '/mcp' => 405,
        '/map.mmd' => 404,
        '/index.yaml' => 404,
        '/TODO.md' => 404,
        '/OBJECTIVE.md' => 404,
    ] as $path => $expected) {
        $response = httpRequest($base . $path);
        if ($response['status'] === 0) {
            usleep(1000000);
            $response = httpRequest($base . $path);
        }
        echo "{$response['status']} GET {$path}\n";
        if ($response['status'] !== $expected) {
            $failures[] = "GET {$path} expected {$expected}, got {$response['status']}";
        }
        if ($expected === 200 && str_contains($response['body'], 'Page not found')) {
            $failures[] = "GET {$path} rendered fallback missing-page content";
        }
    }

    // Compare actual deployed-source bytes through the public HTTP route, without MySQL.
    [$identityStatus,$identity]=(new \App\Services\ReleaseIdentityService())->inspect();
    $release=httpRequest($base.'/is-sha-valid?fingerprint='.$identity['fingerprint']);
    $releaseJson=json_decode($release['body'],true);
    echo "{$release['status']} GET /is-sha-valid\n";
    if ($identityStatus!==200||$release['status']!==200||($releaseJson['scope']??null)!==$identity['scope']||
        ($releaseJson['fingerprint_match']??null)!==true||($releaseJson['fingerprint']??null)!==$identity['fingerprint']||
        !str_contains(strtolower(implode("\n",$release['headers'])),'no-store')) {
        $failures[]='Release source identity HTTP contract did not match local evidence.';
    }
    foreach ([
        '/is-sha-valid?sha[]=bad'=>422,
        '/is-sha-valid?fingerprint='.str_repeat('0',64)=>409,
    ] as $releasePath=>$expected) {
        $check=httpRequest($base.$releasePath);
        if ($check['status']!==$expected) $failures[]="Release identity expected {$expected}, got {$check['status']}.";
    }

    $verify=proc_open([PHP_BINARY,app_path('cli/release-identity.php'),'verify',$base],
        [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$verifyPipes);
    if (is_resource($verify)) {
        fclose($verifyPipes[0]);
        $verified=json_decode(stream_get_contents($verifyPipes[1]),true);
        fclose($verifyPipes[1]);fclose($verifyPipes[2]);
        $verifyExit=proc_close($verify);
        if ($verifyExit!==0||($verified['fingerprint_match']??null)!==true||
            !in_array($verified['state']??'', ['source_and_revision_matched','source_matched_revision_unknown'],true)) {
            $failures[]='Local release verification CLI did not prove the deployed source.';
        }
    } else $failures[]='Unable to launch local release verification CLI.';

    $post = httpRequest($base . '/admin/orders/test/status', 'POST', 'status=shipped');
    echo "{$post['status']} POST /admin/orders/test/status\n";
    if ($post['status'] !== 404) {
        $failures[] = "POST /admin/orders/test/status expected 404, got {$post['status']}";
    }

    $appointmentPost = httpRequest($base . '/consultation/initiate', 'POST', 'consultant_slug=legacy-profile&mode=text_session');
    echo "{$appointmentPost['status']} POST /consultation/initiate\n";
    if ($appointmentPost['status'] !== 302) {
        $failures[] = "POST /consultation/initiate expected guest login redirect 302, got {$appointmentPost['status']}";
    }

    $paymentPost = httpRequest($base . '/payment/verify', 'POST', 'order_id=&payment_id=&signature=');
    echo "{$paymentPost['status']} POST /payment/verify\n";
    if ($paymentPost['status'] !== 404) $failures[]='Retail payment verification must be unreachable.';
    foreach (['/cart/add','/checkout/create-order','/create-order','/verify-payment','/reviews/product','/admin/products/save','/admin/coupons/save'] as $retired) {
        $result=httpRequest($base.$retired,'POST','');
        if ($result['status']!==404) $failures[]='Retired POST route reachable: '.$retired;
    }

    $supportPost = httpRequest($base . '/support/ask', 'POST', 'message=Where%20is%20my%20order%3F');
    echo "{$supportPost['status']} POST /support/ask\n";
    if ($supportPost['status'] === 200 && str_contains($supportPost['body'], 'reply')) {
        // OK
    } elseif (in_array($supportPost['status'], [400, 503], true)) {
        echo "  (MySQL or session unavailable, skip support check)\n";
    } else {
        $failures[] = "POST /support/ask should return a support reply JSON payload";
    }

    foreach ([
        '/api/',
        '/api/shop',
        '/api/categories',
        '/api/product/karuppasami-dollar',
        '/api/temples',
    ] as $path) {
        $response = httpRequest($base . $path);
        json_decode($response['body'], true);
        $ok = json_last_error() === JSON_ERROR_NONE;
        echo ($ok ? 'JSON' : 'BAD') . " {$path}\n";
        if (!$ok) {
            $failures[] = "{$path} did not return valid JSON";
        } elseif (in_array($path,['/api/shop','/api/categories','/api/product/karuppasami-dollar'],true)&&$response['status']!==404) {
            $failures[]='Retired retail API must return 404: '.$path;
        } elseif ($response['status'] === 503) {
            echo "  (MySQL unavailable)\n";
        }
    }

    foreach (['/' => 200, '/missing-discovery-page' => 404, '/blog/missing-discovery-post' => 404] as $path => $status) {
        $response = httpRequest($base . $path, requestHeaders: ['Accept: text/markdown']);
        $headers = implode("\n", $response['headers']);
        if ($response['status'] !== $status || !str_starts_with($response['body'], '# ')
            || !preg_match('/^content-type: text\/markdown\b/im', $headers)
            || !preg_match('/^vary: .*\baccept\b/im', $headers)) {
            $failures[] = "Markdown GET {$path} should return {$status} with negotiated headers";
        }
        if ($status === 200 && !str_contains($headers, 'rel="service-desc"')) $failures[] = 'Public discovery contract link missing';
    }
    $contract = httpRequest($base . '/openapi.json');
    $paths = json_decode($contract['body'], true)['paths'] ?? [];
    if ($contract['status'] !== 200 || array_keys($paths) !== ['/', '/sitemap.xml']) $failures[] = 'Public OpenAPI discovery contract invalid';
    $head = httpRequest($base . '/openapi.json', 'HEAD');
    if ($head['status'] !== 200 || $head['body'] !== '') $failures[] = 'Discovery HEAD must return headers without a body';

    $unknown = httpRequest($base . '/unknown-spa-route');
    echo "{$unknown['status']} GET /unknown-spa-route\n";
    if ($unknown['status'] !== 404) {
        $failures[] = "Unknown route expected 404, got {$unknown['status']}";
    }
    if (!str_contains($unknown['body'], 'Page not found')) {
        $failures[] = "Unknown route should render the PHP 404 page";
    }
} finally {
    foreach ($pipes as $pipe) {
        if (is_resource($pipe)) fclose($pipe);
    }
    proc_terminate($process);
    proc_close($process);
}

if ($failures) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}

echo "PASS local smoke\n";

function waitForServer(string $base): void {
    $deadline = microtime(true) + 8;
    do {
        $response = @httpRequest($base . '/robots.txt');
        if (($response['status'] ?? 0) > 0) return;
        usleep(100000);
    } while (microtime(true) < $deadline);
    throw new RuntimeException('Local PHP server did not start.');
}

function httpRequest(string $url, string $method = 'GET', string $body = '', array $requestHeaders = []): array {
    $options = [
        'method' => $method,
        'ignore_errors' => true,
        'timeout' => 6,
        'header' => $requestHeaders,
    ];
    if ($method === 'POST') {
        $options['header'][] = 'Content-Type: application/x-www-form-urlencoded';
        $options['content'] = $body;
    }
    $content = false;
    $headers = [];
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $context = stream_context_create(['http' => $options]);
        $content = @file_get_contents($url, false, $context);
        $headers = $http_response_header ?? [];
        if ($content !== false || $headers !== []) break;
        usleep(200000);
    }
    preg_match('/\s(\d{3})\s/', $headers[0] ?? '', $matches);
    return [
        'status' => (int)($matches[1] ?? 0),
        'body' => $content === false ? '' : $content,
        'headers' => $headers,
    ];
}
