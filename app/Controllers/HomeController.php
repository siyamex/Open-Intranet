<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Settings;
use App\Core\View;
use App\Core\WidgetService;

final class HomeController
{
    public function index(): void
    {
        $layout = WidgetService::layoutForUser((int) Auth::id());
        View::render('pages/home', [
            'title' => 'Home',
            'layout' => $layout,
            'personalizable' => (bool) Settings::get('allow_widget_personalization', true),
        ]);
    }
}
