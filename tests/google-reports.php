<?php
use App\Integrations\GoogleSiteKit\GoogleSiteKitClient;

$tests['Google configuration preserves saved keys and restricts measurement to general signed-out visits'] = function (): void {
    $settings = GoogleSiteKitClient::settings(['google_site_verification'=>'google-site-verification=token_123',
        'google_reporting_key'=>'', 'google_analytics_id'=>'G-ABCD123456', 'google_ads_id'=>'AW-123456789', 'meta_pixel_id'=>'123456789',
        'google_reporting_property'=>'12345', 'google_search_property'=>'sc-domain:example.org']);
    assertSame('token_123', $settings['google_site_verification'], 'Normalize pasted HTML property token');
    assertTrue(!isset($settings['google_reporting_key']), 'Blank replacement preserves existing key');
    assertSame('', GoogleSiteKitClient::settings(['disconnect_google_reports'=>'1'])['google_reporting_key'], 'Disconnect clears stored key');
    foreach (['google_reporting_property'=>'G-MEASURE', 'google_analytics_id'=>'12345', 'google_site_verification'=>'<meta/>',
        'google_search_property'=>'https://admin:pass@example.com/', 'google_reporting_key'=>'{}'] as $field=>$value) {
        try { GoogleSiteKitClient::settings([$field=>$value]); }
        catch (InvalidArgumentException) { continue; }
        throw new RuntimeException("Invalid $field was accepted");
    }
    assertSame('', GoogleSiteKitClient::measurement($settings, '/', false)['ads'], 'Old ad settings require the clinic to enable use explicitly');
    $enabled = $settings + ['clinic_ads_enabled'=>'1'];
    assertSame('AW-123456789', GoogleSiteKitClient::measurement($enabled, '/', false)['ads'], 'Owner enables advertising destinations');
    foreach (['/contact','/login','/register','/account','/checkout','/support','/campaigns/example','/blog/private-visit','/admin/agent'] as $uri) {
        assertSame([], GoogleSiteKitClient::measurement($enabled, $uri, false), "Do not expose measurement config on $uri");
    }
    assertSame([], GoogleSiteKitClient::measurement($enabled, '/', true), 'Exclude signed-in visits');
    assertSame('', GoogleSiteKitClient::measurement($enabled, '/privacy', true)['page'], 'Allow preference changes on privacy without any measurement');
};

$tests['Google owner reports distinguish scoped metrics, no rows, denied access and cached success'] = function (): void {
    $key = openssl_pkey_new(['private_key_bits'=>2048, 'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($key, $pem);
    $email = 'reader@example.iam.gserviceaccount.com';
    $account = json_encode(['type'=>'service_account','client_email'=>$email,'private_key'=>$pem, 'token_uri'=>'https://example.org/untrusted']);
    $settings = GoogleSiteKitClient::settings(['google_reporting_property'=>'12345','google_search_property'=>'sc-domain:example.org','google_reporting_key'=>$account]);
    assertTrue(!str_contains($settings['google_reporting_key'], 'untrusted'), 'Ignore uploaded token endpoints');
    $client = new GoogleSiteKitClient();
    $cache = [];
    $mode = 'success';
    $calls = [];
    $scopes = [];
    $now = strtotime('2026-11-12 12:00 UTC');
    $post = static function ($url, $body, $token) use (&$calls, &$scopes, &$mode, $key, $email): array {
        $calls[] = $url;
        if ($url === 'https://oauth2.googleapis.com/token') {
            [$header,$claims,$signature] = explode('.', $body['assertion']);
            $payload = json_decode(base64_decode(strtr($claims, '-_', '+/')), true);
            assertSame($email, $payload['iss'], 'Use selected service account');
            assertSame($url, $payload['aud'], 'Token audience is the fixed Google endpoint');
            assertSame(1, openssl_verify("$header.$claims", base64_decode(strtr($signature, '-_', '+/')), openssl_pkey_get_details($key)['key'], OPENSSL_ALGO_SHA256), 'JWT is signed with service account key');
            $scopes[] = $payload['scope'];
            return ['access_token'=>'synthetic-token'];
        }
        assertSame('synthetic-token', $token, 'Read provider reports using the acquired token');
        if (str_starts_with($url, 'https://analyticsdata.googleapis.com/')) {
            assertSame('https://analyticsdata.googleapis.com/v1beta/properties/12345:runReport', $url, 'Use the configured reporting property');
            if ($mode === 'transient') throw new RuntimeException('unavailable', 503);
            if ($mode === 'access') throw new RuntimeException('denied', 403);
            if (isset($body['dimensions'])) return ['rows'=>[['dimensionValues'=>[['value'=>'/about']], 'metricValues'=>[['value'=>'4']]]]];
            return ['rows'=>[['metricValues'=>[['value'=>'8'],['value'=>'6'],['value'=>'4']]]]];
        }
        assertSame('https://www.googleapis.com/webmasters/v3/sites/sc-domain%3Aexample.org/searchAnalytics/query', $url, 'Encode selected Search Console property');
        if ($mode === 'search-success') {
            if (isset($body['dimensions'])) return ['rows'=>[['keys'=>[$body['dimensions'][0] === 'query' ? 'clinic' : 'https://example.org/'], 'clicks'=>2]]];
            return ['rows'=>[['clicks'=>3, 'impressions'=>15, 'ctr'=>0.2, 'position'=>2.3]]];
        }
        return ['responseAggregationType'=>'byProperty'];
    };
    $reports = $client->reports($settings, 7, $cache, $post, $now);
    assertSame('connected', $reports['analytics']['state'], 'GA returned metrics');
    assertSame(6, $reports['analytics']['data']['totals']['Active users'], 'Parse GA4 metrics');
    assertSame(['/about', 4], $reports['analytics']['data']['tables']['Landing pages by sessions'][0], 'Parse landing pages');
    assertSame('empty', $reports['search']['state'], 'Zero Search rows is a valid report');
    assertSame('2026-11-05', $reports['search']['dates']['startDate'], 'Use complete inclusive dates');
    $count = count($calls);
    $client->reports($settings, 7, $cache, $post, $now + 30);
    assertSame($count, count($calls), 'Reuse fresh cached aggregate results');
    $mode = 'transient';
    $reports = $client->reports($settings, 7, $cache, $post, $now + 2000);
    assertSame('stale', $reports['analytics']['state'], 'Preserve prior data on transient error');
    assertSame($now, $reports['analytics']['fetched'], 'Show original freshness');
    $mode = 'access';
    $reports = $client->reports($settings, 7, $cache, $post, $now + 3000);
    assertSame('access', $reports['analytics']['state'], 'Permission error is visible');
    assertSame(null, $reports['analytics']['data'], 'Do not serve cached data after access loss');
    $mode = 'search-success';
    $reports = $client->reports($settings, 28, $cache, $post, $now + 3200);
    assertSame('20%', $reports['search']['data']['totals']['CTR'], 'Format search CTR as a percentage');
    assertSame(['clinic', 2], $reports['search']['data']['tables']['Queries by clicks'][0], 'Keep ranked search query clicks');
    assertSame(['https://example.org/', 2], $reports['search']['data']['tables']['Pages by clicks'][0], 'Keep ranked search page clicks');
    unset($settings['google_search_property']);
    $reports = $client->reports($settings, 90, $cache, $post, $now + 4000);
    assertSame('setup', $reports['search']['state'], 'Show independent setup state');
    assertSame('https://www.googleapis.com/auth/analytics.readonly', end($scopes), 'Request only the scope used');
    assertTrue(!str_contains(json_encode($cache),'synthetic-token') && !str_contains(json_encode($cache),'PRIVATE KEY'), 'Session cache excludes credentials');
};

$tests['integration credentials are write-only and explicit disconnect preserves unrelated settings'] = function (): void {
    $secrets = ['google_reporting_key'=>'synthetic-reporting-key','agent_api_key'=>'synthetic-old-agent-alias'];
    foreach (App\Services\SecretService::FORM_CREDENTIALS as $name=>$label) $secrets[$name] = 'synthetic-secret-'.$name;
    $csrf = 'synthetic-form-token';
    $googleCallbackUri = 'https://example.org/auth/google/callback';
    ob_start();
    require app_path('views/admin/integrations.php');
    $html = ob_get_clean();
    foreach ($secrets as $value) assertTrue(!str_contains($html, $value), 'A saved credential must never reach the form HTML');
    assertTrue(str_contains($html, 'remove_credentials[]'), 'Owner has a scoped disconnect control');
    $ambiguous = false;
    try { App\Services\SecretService::integrationChanges(['smtp_password'=>'new', 'remove_credentials'=>['smtp_password']]); }
    catch (InvalidArgumentException) { $ambiguous = true; }
    assertTrue($ambiguous, 'Require an unambiguous replace or disconnect');
    $process = proc_open([PHP_BINARY, app_path('tests/fixtures/integration-secrets.php')], [], $pipes);
    assertTrue(is_resource($process) && proc_close($process) === 0, 'Encrypted secret-store merge preserves omitted fields and clears legacy fallback aliases');
};
