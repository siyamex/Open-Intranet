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

    private function notFound(): never
    {
        http_response_code(404);
        View::render('errors/404', [], null);
        exit;
    }
}
