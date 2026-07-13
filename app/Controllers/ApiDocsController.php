<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

final class ApiDocsController
{
    public function index(): void
    {
        View::render('pages/api-docs', ['title' => 'API Documentation']);
    }

    public function spec(): void
    {
        $path = BASE_PATH . '/docs/openapi.json';
        header('Content-Type: application/json');
        readfile($path);
        exit;
    }
}
