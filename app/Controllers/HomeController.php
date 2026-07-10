<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Settings;
use App\Core\View;
use App\Models\QuickLink;

final class HomeController
{
    public function index(): void
    {
        $sections = Settings::get('homepage_sections', ['quick_links', 'news', 'gazette']);
        if (!is_array($sections)) {
            $sections = ['quick_links', 'news', 'gazette'];
        }
        $data = ['title' => 'Home', 'sections' => $sections];
        foreach ($sections as $section) {
            if ($section === 'quick_links') {
                $data['quickLinks'] = QuickLink::forUser((int) Auth::id());
            }
            // news + gazette sections attach their data once those modules exist
        }
        View::render('pages/home', $data);
    }
}
