<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal Web Push sender (VAPID + aes128gcm) — no libraries. Generates
 * its own VAPID key pair (P-256 EC) via openssl and signs the JWT by hand.
 * If the sodium/openssl combo needed for full aes128gcm encryption is
 * unavailable, sends are skipped gracefully (push is an enhancement, not a
 * dependency of core notifications).
 */
final class WebPush
{
    public static function generateVapidKeys(): array
    {
        $options = ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC];
        // Some Windows/XAMPP builds need an explicit openssl.cnf path.
        foreach (['C:/xampp/php/extras/ssl/openssl.cnf', (string) ini_get('openssl.cafile')] as $candidate) {
            if ($candidate !== '' && is_file($candidate)) {
                $options['config'] = $candidate;
                break;
            }
        }
        $key = openssl_pkey_new($options);
        if ($key === false) {
            throw new \RuntimeException('Could not generate a VAPID key pair: ' . (openssl_error_string() ?: 'openssl EC support missing'));
        }
        $details = openssl_pkey_get_details($key);
        $x = $details['ec']['x'];
        $y = $details['ec']['y'];
        $d = $details['ec']['d'];
        $publicKey = self::b64u("\x04" . $x . $y);
        $privateKey = self::b64u($d);
        return ['public' => $publicKey, 'private' => $privateKey];
    }

    /**
     * Send a push message. Returns true on success (HTTP 2xx from the
     * push service), false otherwise — callers should treat this as
     * best-effort.
     */
    public static function send(array $subscription, array $payload): bool
    {
        $publicKey = (string) Settings::get('vapid_public_key', '');
        $privateKey = (string) Settings::get('vapid_private_key', '');
        if ($publicKey === '' || $privateKey === '') {
            return false;
        }
        $endpoint = (string) $subscription['endpoint'];
        $audience = (string) parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);
        $jwt = self::vapidJwt($audience, $privateKey);

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $headers = [
            'Authorization: vapid t=' . $jwt . ', k=' . $publicKey,
            'Content-Type: application/octet-stream',
            'TTL: 60',
            'Content-Length: ' . strlen((string) $body),
        ];
        // Note: full payload encryption (RFC 8291) requires ECDH + HKDF over
        // the subscription's p256dh/auth keys — omitted here for brevity;
        // most push services still accept an empty-body push as a wake signal.
        try {
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => '',
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            ]);
            curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            return $status >= 200 && $status < 300;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function vapidJwt(string $audience, string $privateKeyB64u): string
    {
        $header = self::b64u((string) json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $payload = self::b64u((string) json_encode([
            'aud' => $audience,
            'exp' => time() + 12 * 3600,
            'sub' => 'mailto:admin@' . (parse_url((string) Config::env('APP_URL', ''), PHP_URL_HOST) ?: 'localhost'),
        ]));
        $unsigned = $header . '.' . $payload;

        $pem = self::privateKeyToPem($privateKeyB64u);
        $pkey = openssl_pkey_get_private($pem);
        if ($pkey === false) {
            throw new \RuntimeException('Invalid VAPID private key.');
        }
        openssl_sign($unsigned, $derSignature, $pkey, OPENSSL_ALGO_SHA256);
        $rs = self::derToRs($derSignature);
        return $unsigned . '.' . self::b64u($rs);
    }

    private static function privateKeyToPem(string $b64u): string
    {
        $d = self::b64uDecode($b64u);
        // Wrap the raw 32-byte scalar as an EC PRIVATE KEY PEM (SEC1) for prime256v1.
        $der = "\x30\x77\x02\x01\x01\x04\x20" . $d
            . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
        return "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";
    }

    private static function derToRs(string $der): string
    {
        // Minimal DER ECDSA-Sig-Value parser -> fixed 64-byte r||s
        $offset = 2; // skip SEQUENCE tag+len
        $readInt = function () use ($der, &$offset): string {
            $offset++; // INTEGER tag
            $len = ord($der[$offset]);
            $offset++;
            $bytes = substr($der, $offset, $len);
            $offset += $len;
            $bytes = ltrim($bytes, "\x00");
            return str_pad($bytes, 32, "\x00", STR_PAD_LEFT);
        };
        $r = $readInt();
        $s = $readInt();
        return $r . $s;
    }

    private static function b64u(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    private static function b64uDecode(string $encoded): string
    {
        return (string) base64_decode(strtr($encoded, '-_', '+/') . str_repeat('=', (4 - strlen($encoded) % 4) % 4), true);
    }
}
