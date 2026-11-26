<?php
namespace App\Integrations\GoogleSiteKit;

/** Native read-only reports. Google Site Kit itself runs on WordPress. */
final class GoogleSiteKitClient {
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public static function settings(array $input): array {
        $rules = [
            'google_analytics_id' => ['/^G-[A-Z0-9]{4,20}$/D', 'a GA4 measurement ID beginning with G-'],
            'google_ads_id' => ['/^AW-[0-9]{4,20}$/D', 'a numeric Google Ads ID beginning with AW-'],
            'meta_pixel_id' => ['/^[0-9]{5,24}$/D', 'a numeric Meta Pixel ID'],
            'google_site_verification' => ['/^[A-Za-z0-9_-]{1,255}$/D', 'the content token from the Google HTML meta tag'],
            'google_reporting_property' => ['/^[1-9][0-9]{0,19}$/D', 'a numeric GA4 reporting property ID'],
        ];
        foreach ($rules as $key => [$pattern, $hint]) {
            if (!array_key_exists($key, $input)) continue;
            $value = trim(is_string($input[$key]) ? $input[$key] : 'invalid value');
            if ($key === 'google_site_verification') $value = self::verificationToken($value);
            if ($value !== '' && !preg_match($pattern, $value)) throw new \InvalidArgumentException("Enter $hint.");
            $input[$key] = $value;
        }
        if (array_key_exists('google_search_property', $input)) {
            $site = trim(is_string($input['google_search_property']) ? $input['google_search_property'] : 'invalid value');
            $parts = parse_url($site);
            $domain = str_starts_with($site, 'sc-domain:') && filter_var(substr($site, 10), FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
            $urlPrefix = filter_var($site, FILTER_VALIDATE_URL) && in_array($parts['scheme'] ?? '', ['https','http'], true)
                && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['query']) && !isset($parts['fragment']);
            if ($site !== '' && (!$domain && !$urlPrefix || strlen($site) > 500)) {
                throw new \InvalidArgumentException('Enter the exact Search Console URL-prefix or sc-domain property.');
            }
            $input['google_search_property'] = $site;
        }
        if (array_key_exists('google_pagespeed_key',$input)) $input['google_pagespeed_key']=PageSpeedClient::apiKey($input['google_pagespeed_key']);
        $json = $input['google_reporting_key'] ?? '';
        unset($input['google_reporting_key']);
        if (!empty($input['disconnect_google_reports'])) {
            if ($json !== '') throw new \InvalidArgumentException('Choose either a replacement key or disconnect for Google reporting.');
            $input['google_reporting_key'] = '';
        } elseif ($json !== '') {
            $account = self::serviceAccount($json);
            $input['google_reporting_key'] = json_encode($account, JSON_THROW_ON_ERROR);
        }
        unset($input['disconnect_google_reports']);
        return $input;
    }

    public static function verificationToken(string $value): string {
        return trim(preg_replace('/^google-site-verification=/i', '', trim($value)));
    }

    /** Only these public, non-clinical landing pages have a measurable page name. */
    public static function measurement(array $settings, string $uri, bool $signedIn): array {
        $path = parse_url($uri, PHP_URL_PATH);
        if (!in_array($path, ['/', '/about', '/privacy'], true) || ($signedIn && $path !== '/privacy')) return [];
        $ga = (string)($settings['google_analytics_id'] ?? '');
        $ads = (string)($settings['google_ads_id'] ?? '');
        $pixel = (string)($settings['meta_pixel_id'] ?? '');
        $adsEnabled = ($settings['clinic_ads_enabled'] ?? '') === '1';
        $ga = preg_match('/^G-[A-Z0-9]{4,20}$/D', $ga) ? $ga : '';
        $ads = $adsEnabled && preg_match('/^AW-[0-9]{4,20}$/D', $ads) ? $ads : '';
        $pixel = $adsEnabled ? (new \App\Integrations\MetaPixel\MetaPixelClient($pixel))->pixelId() : '';
        if ($ga === '' && $ads === '' && $pixel === '') return [];
        $page = $path === '/' ? 'home' : ($path === '/about' ? 'about' : '');
        return compact('ga', 'ads', 'pixel', 'page');
    }

    private static function serviceAccount(mixed $json): array {
        $account = is_string($json) && strlen($json) < 32768 ? json_decode($json, true) : null;
        $privateKey = is_string($account['private_key'] ?? null) ? openssl_pkey_get_private($account['private_key']) : false;
        if (!is_array($account) || ($account['type'] ?? '') !== 'service_account'
            || !filter_var($account['client_email'] ?? '', FILTER_VALIDATE_EMAIL)
            || !$privateKey) {
            throw new \InvalidArgumentException('Enter a Google service account JSON key with an email and a valid signing key.');
        }
        if ((openssl_pkey_get_details($privateKey)['type'] ?? null) !== OPENSSL_KEYTYPE_RSA) {
            throw new \InvalidArgumentException('Google reporting requires an RSA service account signing key.');
        }
        // Never use token_uri or other request destinations supplied by uploaded JSON.
        return ['type'=>'service_account', 'client_email'=>$account['client_email'], 'private_key'=>$account['private_key']];
    }

    /** Cache aggregate results only in this owner's session. Never persist access tokens or provider errors. */
    public function reports(array $settings, int $days, array &$cache, ?callable $post = null, ?int $now = null): array {
        $now ??= time();
        $days = in_array($days, [7, 28, 90], true) ? $days : 28;
        $dates = ['startDate'=>gmdate('Y-m-d', $now - $days * 86400), 'endDate'=>gmdate('Y-m-d', $now - 86400)];
        $properties = ['analytics'=>(string)($settings['google_reporting_property'] ?? ''), 'search'=>(string)($settings['google_search_property'] ?? '')];
        $key = (string)($settings['google_reporting_key'] ?? '');
        $identity = hash('sha256', json_encode([$properties, $key], JSON_THROW_ON_ERROR));
        if (($cache['identity'] ?? '') !== $identity) $cache = ['identity'=>$identity, 'ranges'=>[]];
        $post ??= self::post(...);
        $token = null;
        $tokenError = null;
        $reports = [];
        foreach ($properties as $source => $property) {
            $state = ['property'=>$property, 'dates'=>$dates, 'state'=>'setup', 'fetched'=>null, 'data'=>null];
            if ($property === '' || $key === '') {
                $reports[$source] = $state;
                continue;
            }
            $prior = $cache['ranges'][$days][$source] ?? null;
            $sameDates = ($prior['dates'] ?? []) === $dates;
            if ($sameDates && $prior['fetched'] > $now - 1800) {
                $reports[$source] = $prior;
                continue;
            }
            try {
                if ($tokenError) throw $tokenError;
                if ($token === null) {
                    try {
                        $token = $this->token($key, $properties, $post, $now);
                    } catch (\RuntimeException $failure) {
                        $tokenError = $failure;
                        throw $failure;
                    }
                }
                $data = $source === 'analytics' ? $this->analytics($property, $dates, $token, $post) : $this->search($property, $dates, $token, $post);
                $state['state'] = $data['totals'] === [] && !array_filter($data['tables']) ? 'empty' : 'connected';
                $state['data'] = $data;
                $state['fetched'] = $now;
                $cache['ranges'][$days][$source] = $state;
            } catch (\Throwable $failure) {
                if (in_array($failure->getCode(), [400,401,403,404], true)) {
                    foreach ($cache['ranges'] as &$range) unset($range[$source]);
                    unset($range);
                    $state['state'] = 'access';
                } elseif ($prior && $prior['fetched'] > $now - 7 * 86400) {
                    $state = $prior;
                    $state['state'] = 'stale';
                } else {
                    $state['state'] = 'unavailable';
                }
            }
            $reports[$source] = $state;
        }
        return $reports;
    }

    private function token(string $key, array $properties, callable $post, int $now): string {
        // https://developers.google.com/identity/protocols/oauth2/service-account#authorizingrequests
        $account = self::serviceAccount($key);
        $scopes = [];
        if ($properties['analytics'] !== '') $scopes[] = 'https://www.googleapis.com/auth/analytics.readonly';
        if ($properties['search'] !== '') $scopes[] = 'https://www.googleapis.com/auth/webmasters.readonly';
        $encode = static fn(string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $claims = ['iss'=>$account['client_email'], 'scope'=>implode(' ', $scopes), 'aud'=>self::TOKEN_URL, 'iat'=>$now, 'exp'=>$now+3600];
        $unsigned = $encode('{"alg":"RS256","typ":"JWT"}') . '.' . $encode(json_encode($claims, JSON_THROW_ON_ERROR));
        if (!openssl_sign($unsigned, $signature, $account['private_key'], OPENSSL_ALGO_SHA256)) throw new \RuntimeException('Signing failed', 401);
        $result = $post(self::TOKEN_URL, ['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion'=>$unsigned.'.'.$encode($signature)], '');
        if (!is_string($result['access_token'] ?? null) || $result['access_token'] === '') throw new \RuntimeException('Token unavailable');
        return $result['access_token'];
    }

    private function analytics(string $property, array $dates, string $token, callable $post): array {
        // https://developers.google.com/analytics/devguides/reporting/data/v1/rest/v1beta/properties/runReport
        $url = 'https://analyticsdata.googleapis.com/v1beta/properties/'.rawurlencode($property).':runReport';
        $metrics = ['sessions'=>'Sessions', 'activeUsers'=>'Active users', 'engagedSessions'=>'Engaged sessions'];
        $base = ['dateRanges'=>[$dates], 'metrics'=>array_map(static fn($name)=>['name'=>$name], array_keys($metrics))];
        $summary = $post($url, $base, $token);
        $totals = [];
        if (isset($summary['rows'][0])) {
            foreach (array_values($metrics) as $i => $label) $totals[$label] = $this->metric($summary['rows'][0]['metricValues'][$i]['value'] ?? null);
        }
        $pageData = $post($url, ['dateRanges'=>[$dates], 'metrics'=>[['name'=>'sessions']], 'dimensions'=>[['name'=>'landingPage']],
            'orderBys'=>[['metric'=>['metricName'=>'sessions'], 'desc'=>true]], 'limit'=>'10'], $token);
        $pages = [];
        foreach ($pageData['rows'] ?? [] as $row) {
            $name = $row['dimensionValues'][0]['value'] ?? null;
            if (!is_string($name)) throw new \UnexpectedValueException('Invalid landing page row.');
            $pages[] = [$name, $this->metric($row['metricValues'][0]['value'] ?? null)];
        }
        return ['totals'=>$totals, 'tables'=>['Landing pages by sessions'=>$pages]];
    }

    private function metric(mixed $value): int {
        if (!is_numeric($value)) throw new \UnexpectedValueException('Invalid report.');
        return (int)$value;
    }

    private function search(string $property, array $dates, string $token, callable $post): array {
        // https://developers.google.com/webmaster-tools/v1/api_reference_index
        $url = 'https://www.googleapis.com/webmasters/v3/sites/'.rawurlencode($property).'/searchAnalytics/query';
        $summary = $post($url, $dates + ['rowLimit'=>1], $token);
        $totals = [];
        if (isset($summary['rows'][0])) {
            $row = $summary['rows'][0];
            if (!is_numeric($row['ctr'] ?? null) || !is_numeric($row['position'] ?? null)) throw new \UnexpectedValueException('Invalid report.');
            $totals = ['Clicks'=>$this->metric($row['clicks'] ?? null), 'Impressions'=>$this->metric($row['impressions'] ?? null),
                'CTR'=>round(100*$row['ctr'],2).'%', 'Average position'=>round((float)$row['position'],1)];
        }
        $tables = [];
        foreach (['query'=>'Queries by clicks','page'=>'Pages by clicks'] as $dimension=>$title) {
            $data = $post($url, $dates + ['dimensions'=>[$dimension], 'rowLimit'=>10], $token);
            $tables[$title] = [];
            foreach ($data['rows'] ?? [] as $row) {
                $name = $row['keys'][0] ?? null;
                if (!is_string($name)) throw new \UnexpectedValueException('Invalid search row.');
                $tables[$title][] = [$name, $this->metric($row['clicks'] ?? null)];
            }
        }
        return ['totals'=>$totals, 'tables'=>$tables];
    }

    private static function post(string $url, array $body, string $token): array {
        $json = $token !== '';
        $headers = ['Content-Type: '.($json ? 'application/json' : 'application/x-www-form-urlencoded')];
        if ($json) $headers[] = 'Authorization: Bearer '.$token;
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_CONNECTTIMEOUT=>3,
            CURLOPT_TIMEOUT=>6, CURLOPT_HTTPHEADER=>$headers, CURLOPT_POSTFIELDS=>$json ? json_encode($body, JSON_THROW_ON_ERROR) : http_build_query($body)]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if ($status === 403 && (($data['error']['status'] ?? '') === 'RESOURCE_EXHAUSTED'
            || array_intersect(array_column((array)($data['error']['errors'] ?? []), 'reason'), ['rateLimitExceeded', 'userRateLimitExceeded', 'quotaExceeded', 'dailyLimitExceeded']))) {
            $status = 429;
        }
        if ($status !== 200 || !is_array($data) || isset($data['error'])) throw new \RuntimeException('Google report request failed', $status);
        return $data;
    }
}
