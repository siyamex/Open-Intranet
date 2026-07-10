<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

/**
 * Serves avatar images from storage/uploads/avatars for signed-in users.
 * Documents get their own permission-checked /files/{uuid} route.
 */
final class MediaController
{
    public function avatar(string $file): void
    {
        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $file)) {
            $this->notFound();
        }
        $path = BASE_PATH . '/storage/uploads/avatars/' . $file;
        if (!is_file($path)) {
            $this->notFound();
        }
        $mime = match (pathinfo($file, PATHINFO_EXTENSION)) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=86400');
        readfile($path);
        exit;
    }

    public function newsMedia(string $file): void
    {
        if (!preg_match('/^[a-f0-9]{32}\.jpg$/', $file)) {
            $this->notFound();
        }
        $path = BASE_PATH . '/storage/uploads/news/' . $file;
        if (!is_file($path)) {
            $this->notFound();
        }
        header('Content-Type: image/jpeg');
        header('Content-Length: ' . (string) filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=86400');
        readfile($path);
        exit;
    }

    public function quickLinkIcon(string $file): void
    {
        if (!preg_match('/^[a-f0-9]{24}\.(svg|png)$/', $file)) {
            $this->notFound();
        }
        $path = BASE_PATH . '/storage/uploads/qlicons/' . $file;
        if (!is_file($path)) {
            $this->notFound();
        }
        $mime = str_ends_with($file, '.svg') ? 'image/svg+xml' : 'image/png';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'');
        header('Cache-Control: private, max-age=86400');
        readfile($path);
        exit;
    }

    /**
     * Uploaded theme assets (public — themes style the login page too).
     * Files were finfo-verified and re-encoded/sanitized at install time.
     */
    public function themeAssetSub(string $slug, string $sub, string $file): void
    {
        $this->themeAsset($slug, $file, $sub);
    }

    public function themeAsset(string $slug, string $file, string $sub = ''): void
    {
        $slug = (string) preg_replace('/[^a-z0-9-]/', '', strtolower($slug));
        $relative = ($sub !== '' ? $sub . '/' : '') . $file;
        if ($slug === '' || !preg_match('#^[a-zA-Z0-9_./-]+$#', $relative) || str_contains($relative, '..')) {
            $this->notFound();
        }
        $path = BASE_PATH . '/themes/uploaded/' . $slug . '/assets/' . $relative;
        $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        $mimes = [
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp',
            'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'css' => 'text/css',
            'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'otf' => 'font/otf',
        ];
        if (!is_file($path) || !isset($mimes[$ext])) {
            $this->notFound();
        }
        header('Content-Type: ' . $mimes[$ext]);
        header('Content-Length: ' . (string) filesize($path));
        header('X-Content-Type-Options: nosniff');
        if ($ext === 'svg') {
            header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");
        }
        header('Cache-Control: public, max-age=604800');
        readfile($path);
        exit;
    }

    private function notFound(): never
    {
        http_response_code(404);
        View::render('errors/404', [], null);
        exit;
    }
}
