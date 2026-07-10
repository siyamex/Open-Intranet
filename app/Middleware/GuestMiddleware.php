<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;

final class GuestMiddleware
{
    public function handle(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
    }
}
