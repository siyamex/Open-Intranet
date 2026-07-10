<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Small curl wrapper used for OIDC discovery/token calls and avatar
 * downloads. HTTPS is enforced except for localhost (local IdPs in dev).
 */
final class Http
{
    /**
     * @return array{status: int, body: string}
     */
    public static function get(string $url, array $headers = [], int $timeout = 10): array
    {
        return self::request('GET', $url, null, $headers, $timeout);
    }

    /**
     * @return array{status: int, body: string}
     */
    public static function postForm(string $url, array $data, array $headers = [], int $timeout = 10): array
    {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        return self::request('POST', $url, http_build_query($data), $headers, $timeout);
    }

    /**
     * @return array<mixed> decoded JSON
     */
    public static function getJson(string $url, int $timeout = 10): array
    {
        $response = self::get($url, ['Accept: application/json'], $timeout);
        if ($response['status'] < 200 || $response['status'] >= 300) {
            throw new \RuntimeException("HTTP {$response['status']} fetching {$url}");
        }
        $data = json_decode($response['body'], true);
        if (!is_array($data)) {
            throw new \RuntimeException("Invalid JSON from {$url}");
        }
        return $data;
    }

    /**
     * SSRF guard: https only (http allowed for localhost in local dev),
     * and the hostname must not resolve to private/link-local/loopback
     * ranges. Note: curl re-resolves, so a tiny TOCTOU window remains —
     * acceptable for admin-configured IdP endpoints.
     */
    public static function assertAllowedUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $isDevLocalhost = Config::env('APP_ENV', 'production') === 'local'
            && in_array($host, ['localhost', '127.0.0.1', '::1'], true);
        if ($scheme !== 'https' && !($scheme === 'http' && $isDevLocalhost)) {
            throw new \RuntimeException('Only https:// URLs are allowed: ' . $url);
        }
        if ($isDevLocalhost) {
            return;
        }
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            self::assertPublicIp($host, $url);
            return;
        }
        $ips = gethostbynamel($host);
        if ($ips === false || $ips === []) {
            throw new \RuntimeException('Hostname does not resolve: ' . $host);
        }
        foreach ($ips as $ip) {
            self::assertPublicIp($ip, $url);
        }
    }

    private static function assertPublicIp(string $ip, string $url): void
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            throw new \RuntimeException('URL resolves to a private/reserved address and was blocked: ' . $url);
        }
    }

    /**
     * @return array{status: int, body: string}
     */
    private static function request(string $method, string $url, ?string $body, array $headers, int $timeout): array
    {
        self::assertAllowedUrl($url);
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('curl_init failed');
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'OpenIntranet/1.0',
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body ?? '');
        }
        $responseBody = curl_exec($ch);
        if ($responseBody === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("HTTP request failed: {$error}");
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return ['status' => $status, 'body' => (string) $responseBody];
    }
}
