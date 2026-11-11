<?php
namespace App\Integrations\GoogleOAuth;

final class GoogleOAuthClient {
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    public function __construct(private string $clientId, private string $clientSecret) {}

    public function authorizationUrl(string $redirectUri, string $state, string $nonce): string {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'=>$this->clientId, 'redirect_uri'=>$redirectUri, 'response_type'=>'code',
            'scope'=>'openid email', 'state'=>$state, 'nonce'=>$nonce,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function exchange(string $code, string $redirectUri, string $nonce, ?callable $post = null): array {
        $token = ($post ?? self::post(...))(self::TOKEN_ENDPOINT, [
            'code'=>$code, 'client_id'=>$this->clientId, 'client_secret'=>$this->clientSecret,
            'redirect_uri'=>$redirectUri, 'grant_type'=>'authorization_code',
        ]);

        // Accept ID tokens only from the direct, client-authenticated HTTPS code exchange.
        // Google's server flow permits trusting the token's origin on this TLS channel:
        // https://developers.google.com/identity/openid-connect/openid-connect#obtainuserinfo
        $jwt = is_string($token['id_token'] ?? null) ? explode('.', $token['id_token']) : [];
        $payload = count($jwt) === 3 && $jwt[0] !== '' && $jwt[2] !== ''
            ? base64_decode(strtr($jwt[1], '-_', '+/'), true) : false;
        $claims = $payload !== false ? json_decode($payload, true) : null;
        if (!is_array($claims)
            || !in_array($claims['iss'] ?? null, ['https://accounts.google.com', 'accounts.google.com'], true)
            || ($claims['aud'] ?? null) !== $this->clientId
            || (array_key_exists('azp', $claims) && $claims['azp'] !== $this->clientId)
            || !is_int($claims['exp'] ?? null) || $claims['exp'] <= time()
            || !is_string($claims['nonce'] ?? null) || !hash_equals($nonce, $claims['nonce'])
            || !is_string($claims['sub'] ?? null) || !preg_match('/^[\x21-\x7E]{1,255}$/D', $claims['sub'])
            || !is_string($claims['email'] ?? null) || !filter_var($claims['email'], FILTER_VALIDATE_EMAIL)
            || !in_array($claims['email_verified'] ?? null, [true, 'true'], true)) {
            throw new \UnexpectedValueException('Invalid Google identity.');
        }
        return ['sub'=>$claims['sub'], 'email'=>$claims['email']];
    }

    private static function post(string $url, array $fields): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>http_build_query($fields),
            CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_TIMEOUT=>10, CURLOPT_FOLLOWLOCATION=>false,
            CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2,
        ]);
        $body = curl_exec($ch);
        $result = is_string($body) ? json_decode($body, true) : null;
        if (curl_getinfo($ch, CURLINFO_RESPONSE_CODE) !== 200 || !is_array($result) || isset($result['error'])) {
            throw new \RuntimeException('Google token exchange failed.');
        }
        return $result;
    }
}
