<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

/**
 * Global search — the navbar box posts here. Full unified search
 * (FULLTEXT across modules) lands in the search phase; this page is the
 * stable endpoint it will fill in.
 */
final class SearchController
{
    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        View::render('pages/search', [
            'title' => 'Search',
            'q' => $q,
            'results' => null, // populated once unified search is built
        ]);
    }
}
