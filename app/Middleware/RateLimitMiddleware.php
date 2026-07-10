<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\View;

/**
 * Central token-bucket rate limiter, file-backed in storage/cache/ratelimit.
 * Route usage: RateLimitMiddleware::class . ':login,5,300'
 *   -> bucket "login", 5 requests, refilled over 300 seconds, keyed by IP.
 */
final class RateLimitMiddleware
{
    public function handle(?string $param = null): void
    {
        if ($param === null) {
            return;
        }
        [$bucket, $capacity, $window] = array_pad(explode(',', $param), 3, null);
        $capacity = max(1, (int) $capacity);
        $window = max(1, (int) $window);
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        if (!self::consume($bucket . ':' . $ip, $capacity, $window)) {
            header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' 429 Too Many Requests');
            header('Retry-After: ' . $window);
            View::render('errors/429', [], null);
            exit;
        }
    }

    /**
     * Take one token from the bucket; returns false when empty.
     */
    public static function consume(string $key, int $capacity, int $windowSeconds): bool
    {
        $dir = BASE_PATH . '/storage/cache/ratelimit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $dir . '/' . sha1($key) . '.json';
        $now = microtime(true);
        $handle = fopen($file, 'c+');
        if ($handle === false) {
            return true; // never lock users out on IO failure
        }
        flock($handle, LOCK_EX);
        $raw = stream_get_contents($handle);
        $state = json_decode((string) $raw, true);
        if (!is_array($state)) {
            $state = ['tokens' => (float) $capacity, 'updated' => $now];
        }
        // refill
        $rate = $capacity / $windowSeconds; // tokens per second
        $state['tokens'] = min((float) $capacity, (float) $state['tokens'] + ($now - (float) $state['updated']) * $rate);
        $state['updated'] = $now;
        $allowed = $state['tokens'] >= 1.0;
        if ($allowed) {
            $state['tokens'] -= 1.0;
        }
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) json_encode($state));
        flock($handle, LOCK_UN);
        fclose($handle);
        return $allowed;
    }
}
