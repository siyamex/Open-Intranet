<?php

declare(strict_types=1);

namespace App\Core\Sso;

use App\Core\Config;
use App\Core\Crypto;
use App\Core\Http;

/**
 * Pure-PHP OpenID Connect client: Authorization Code flow with PKCE,
 * discovery + JWKS caching, and full RS256 ID-token validation using a
 * public key reconstructed from the JWK modulus/exponent.
 */
final class OidcClient
{
    private const CACHE_TTL = 43200; // 12h

    public function __construct(private array $provider)
    {
    }

    // ---- Discovery -------------------------------------------------------

    public function discoveryUrl(): string
    {
        $type = (string) $this->provider['type'];
        if ($type === 'google') {
            return 'https://accounts.google.com/.well-known/openid-configuration';
        }
        if ($type === 'microsoft') {
            $tenant = trim((string) ($this->provider['tenant_or_issuer'] ?? '')) ?: 'common';
            return "https://login.microsoftonline.com/{$tenant}/v2.0/.well-known/openid-configuration";
        }
        $explicit = trim((string) ($this->provider['discovery_url'] ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }
        $issuer = rtrim(trim((string) ($this->provider['tenant_or_issuer'] ?? '')), '/');
        if ($issuer !== '') {
            return $issuer . '/.well-known/openid-configuration';
        }
        throw new \RuntimeException('Provider has no discovery URL configured.');
    }

    /**
     * @return array<string, mixed> the provider's openid-configuration
     */
    public function discovery(bool $fresh = false): array
    {
        $doc = $this->cachedJson('discovery', $this->discoveryUrl(), $fresh);
        foreach (['issuer', 'authorization_endpoint', 'token_endpoint', 'jwks_uri'] as $key) {
            if (empty($doc[$key])) {
                throw new \RuntimeException("Discovery document is missing '{$key}'.");
            }
        }
        return $doc;
    }

    /**
     * @return array<string, mixed> the JWKS document
     */
    public function jwks(bool $fresh = false): array
    {
        $jwks = $this->cachedJson('jwks', (string) $this->discovery($fresh)['jwks_uri'], $fresh);
        if (empty($jwks['keys']) || !is_array($jwks['keys'])) {
            throw new \RuntimeException('JWKS document contains no keys.');
        }
        return $jwks;
    }

    // ---- Authorization ----------------------------------------------------

    public static function generateCodeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    }

    public function authUrl(string $redirectUri, string $state, string $nonce, string $codeVerifier): string
    {
        $challenge = self::b64uEncode(hash('sha256', $codeVerifier, true));
        $params = [
            'response_type' => 'code',
            'client_id' => (string) $this->provider['client_id'],
            'redirect_uri' => $redirectUri,
            'scope' => (string) ($this->provider['scopes'] ?: 'openid profile email'),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ];
        return $this->discovery()['authorization_endpoint'] . '?' . http_build_query($params);
    }

    /**
     * Exchange the authorization code for tokens.
     *
     * @return array<string, mixed> token endpoint response (id_token, access_token, ...)
     */
    public function exchangeCode(string $code, string $redirectUri, string $codeVerifier): array
    {
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => (string) $this->provider['client_id'],
            'code_verifier' => $codeVerifier,
        ];
        $secretEncrypted = (string) ($this->provider['client_secret_encrypted'] ?? '');
        if ($secretEncrypted !== '') {
            $data['client_secret'] = Crypto::decrypt($secretEncrypted);
        }
        $response = Http::postForm((string) $this->discovery()['token_endpoint'], $data, ['Accept: application/json']);
        $json = json_decode($response['body'], true);
        if (!is_array($json)) {
            throw new \RuntimeException('Token endpoint returned invalid JSON.');
        }
        if ($response['status'] >= 400 || isset($json['error'])) {
            $desc = (string) ($json['error_description'] ?? $json['error'] ?? ('HTTP ' . $response['status']));
            throw new \RuntimeException('Token exchange failed: ' . $desc);
        }
        if (empty($json['id_token'])) {
            throw new \RuntimeException('Token response did not include an id_token.');
        }
        return $json;
    }

    // ---- ID token validation ----------------------------------------------

    /**
     * Fully validate the ID token: RS256 signature against the JWKS,
     * then iss / aud / exp / iat / nonce.
     *
     * @return array<string, mixed> the verified claims
     */
    public function validateIdToken(string $jwt, string $expectedNonce): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new \RuntimeException('ID token is not a valid JWT.');
        }
        [$headB64, $payloadB64, $sigB64] = $parts;
        $header = json_decode(self::b64uDecode($headB64), true);
        $claims = json_decode(self::b64uDecode($payloadB64), true);
        if (!is_array($header) || !is_array($claims)) {
            throw new \RuntimeException('ID token header/payload could not be decoded.');
        }
        if (($header['alg'] ?? '') !== 'RS256') {
            throw new \RuntimeException('Unsupported ID token algorithm: ' . (string) ($header['alg'] ?? 'none'));
        }

        $jwk = $this->findJwk((string) ($header['kid'] ?? ''));
        $pem = self::jwkToPem((string) $jwk['n'], (string) $jwk['e']);
        $publicKey = openssl_pkey_get_public($pem);
        if ($publicKey === false) {
            throw new \RuntimeException('Failed to load the reconstructed RSA public key.');
        }
        $verified = openssl_verify(
            $headB64 . '.' . $payloadB64,
            self::b64uDecode($sigB64),
            $publicKey,
            OPENSSL_ALGO_SHA256
        );
        if ($verified !== 1) {
            throw new \RuntimeException('ID token signature verification failed.');
        }

        $this->assertIssuer((string) ($claims['iss'] ?? ''));

        $aud = $claims['aud'] ?? null;
        $audiences = is_array($aud) ? $aud : [$aud];
        if (!in_array((string) $this->provider['client_id'], array_map('strval', $audiences), true)) {
            throw new \RuntimeException('ID token audience does not match the client_id.');
        }

        $leeway = 300;
        $now = time();
        if (!isset($claims['exp']) || (int) $claims['exp'] < $now - $leeway) {
            throw new \RuntimeException('ID token has expired.');
        }
        if (isset($claims['iat']) && (int) $claims['iat'] > $now + $leeway) {
            throw new \RuntimeException('ID token issued in the future (clock skew?).');
        }
        if (!isset($claims['nonce']) || !hash_equals($expectedNonce, (string) $claims['nonce'])) {
            throw new \RuntimeException('ID token nonce mismatch.');
        }
        return $claims;
    }

    /**
     * Live diagnostic used by the admin "Test configuration" button.
     *
     * @return array<int, array{ok: bool, label: string, detail: string}>
     */
    public function testConfiguration(): array
    {
        $results = [];
        $check = static function (bool $ok, string $label, string $detail) use (&$results): void {
            $results[] = ['ok' => $ok, 'label' => $label, 'detail' => $detail];
        };

        try {
            $url = $this->discoveryUrl();
            $check(true, 'Discovery URL', $url);
        } catch (\Throwable $e) {
            $check(false, 'Discovery URL', $e->getMessage());
            return $results;
        }
        try {
            $doc = $this->discovery(true);
            $check(true, 'Discovery document', 'issuer: ' . (string) $doc['issuer']);
            $check(true, 'Endpoints', 'authorize + token + jwks endpoints present');
        } catch (\Throwable $e) {
            $check(false, 'Discovery document', $e->getMessage());
            return $results;
        }
        try {
            $jwks = $this->jwks(true);
            $rsaKeys = array_filter($jwks['keys'], static fn ($k) => ($k['kty'] ?? '') === 'RSA');
            $check(count($rsaKeys) > 0, 'JWKS', count($rsaKeys) . ' RSA signing key(s) found');
        } catch (\Throwable $e) {
            $check(false, 'JWKS', $e->getMessage());
        }
        $check(trim((string) $this->provider['client_id']) !== '', 'Client ID', trim((string) $this->provider['client_id']) !== '' ? 'set' : 'missing — paste it from your IdP app registration');
        $check(
            !empty($this->provider['client_secret_encrypted']),
            'Client secret',
            !empty($this->provider['client_secret_encrypted']) ? 'set (encrypted at rest)' : 'not set — required unless the IdP allows public PKCE clients'
        );
        return $results;
    }

    // ---- Internals ----------------------------------------------------------

    private function assertIssuer(string $iss): void
    {
        $expected = (string) $this->discovery()['issuer'];
        $type = (string) $this->provider['type'];
        $tenant = strtolower(trim((string) ($this->provider['tenant_or_issuer'] ?? ''))) ?: 'common';
        if ($type === 'microsoft' && in_array($tenant, ['common', 'organizations', 'consumers'], true)) {
            // Multi-tenant: the discovery issuer contains a {tenantid} template;
            // the token carries the caller's real tenant GUID.
            if (!preg_match('#^https://login\.microsoftonline\.com/[0-9a-f-]{36}/v2\.0$#i', $iss)) {
                throw new \RuntimeException('ID token issuer is not a valid Microsoft tenant issuer.');
            }
            return;
        }
        if ($iss !== $expected) {
            throw new \RuntimeException('ID token issuer mismatch.');
        }
    }

    /**
     * @return array<string, mixed> the matching RSA JWK
     */
    private function findJwk(string $kid): array
    {
        // First try the cached JWKS, then refresh once (key rotation).
        foreach ([false, true] as $fresh) {
            foreach ($this->jwks($fresh)['keys'] as $key) {
                if (($key['kty'] ?? '') !== 'RSA' || empty($key['n']) || empty($key['e'])) {
                    continue;
                }
                if (($key['use'] ?? 'sig') !== 'sig') {
                    continue;
                }
                if ($kid === '' || ($key['kid'] ?? '') === $kid) {
                    return $key;
                }
            }
        }
        throw new \RuntimeException('No matching RSA key found in the JWKS for kid=' . $kid);
    }

    /**
     * Convert a JWK RSA modulus/exponent (base64url) into a PEM public key:
     * hand-rolled ASN.1/DER SubjectPublicKeyInfo encoding.
     */
    public static function jwkToPem(string $n, string $e): string
    {
        $modulus = self::b64uDecode($n);
        $exponent = self::b64uDecode($e);

        $der = self::derSequence(
            self::derSequence(
                "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01" // OID 1.2.840.113549.1.1.1 (rsaEncryption)
                . "\x05\x00"                                     // NULL params
            )
            . self::derBitString(
                self::derSequence(
                    self::derInteger($modulus) . self::derInteger($exponent)
                )
            )
        );
        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    private static function derLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        $bytes = ltrim(pack('N', $length), "\x00");
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private static function derSequence(string $contents): string
    {
        return "\x30" . self::derLength(strlen($contents)) . $contents;
    }

    private static function derInteger(string $bytes): string
    {
        $bytes = ltrim($bytes, "\x00");
        if ($bytes === '' || (ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00" . $bytes; // keep the integer positive
        }
        return "\x02" . self::derLength(strlen($bytes)) . $bytes;
    }

    private static function derBitString(string $contents): string
    {
        $contents = "\x00" . $contents; // zero unused bits
        return "\x03" . self::derLength(strlen($contents)) . $contents;
    }

    public static function b64uEncode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    public static function b64uDecode(string $encoded): string
    {
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
        if ($decoded === false) {
            throw new \RuntimeException('Invalid base64url data.');
        }
        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function cachedJson(string $kind, string $url, bool $fresh): array
    {
        $file = BASE_PATH . '/storage/cache/oidc_' . preg_replace('/[^a-z0-9_-]/i', '_', (string) $this->provider['slug']) . '_' . $kind . '.json';
        if (!$fresh && is_file($file) && (time() - (int) filemtime($file)) < self::CACHE_TTL) {
            $cached = json_decode((string) file_get_contents($file), true);
            if (is_array($cached)) {
                return $cached;
            }
        }
        $data = Http::getJson($url);
        @file_put_contents($file, json_encode($data), LOCK_EX);
        return $data;
    }
}
