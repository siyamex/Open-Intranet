<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Global security headers. CSP is self-only for scripts (all JS lives in
 * files); styles allow inline attributes which the UI uses extensively.
 * For production behind HTTPS, enable HSTS at the web server:
 *   Strict-Transport-Security: max-age=31536000; includeSubDomains
 */
final class SecurityHeadersMiddleware
{
    public function handle(?string $param = null): void
    {
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
            . "img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; "
            . "frame-ancestors 'self'; form-action 'self'; base-uri 'self'");
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    }
}
