<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Flash;
use App\Core\LoginThrottle;
use App\Core\Settings;
use App\Core\Validator;
use App\Core\View;

final class AuthController
{
    public function showLogin(): void
    {
        $providers = DB::fetchAll(
            'SELECT id, name, slug, icon, button_color FROM sso_providers WHERE enabled = 1 ORDER BY sort_order, id'
        );
        View::render('auth/login', [
            'title' => 'Sign in',
            'providers' => $providers,
            'allowLocal' => (bool) Settings::get('allow_local_login', true),
        ], 'auth');
    }

    public function login(): void
    {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $remember = !empty($_POST['remember']);
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        $v = new Validator($_POST, ['email' => 'required|email', 'password' => 'required']);
        if ($v->fails()) {
            flash('error', 'Please enter your email address and password.');
            Flash::keepInput();
            redirect('login');
        }

        if (LoginThrottle::tooMany($email, $ip)) {
            $minutes = LoginThrottle::minutesLeft($email, $ip);
            flash('error', "Too many failed sign-in attempts. Please try again in about {$minutes} minute(s).");
            Flash::keepInput();
            redirect('login');
        }

        $user = DB::fetch('SELECT * FROM users WHERE email = ?', [$email]);
        $valid = $user !== null
            && $user['status'] === 'active'
            && $user['password_hash'] !== null
            && password_verify($password, (string) $user['password_hash']);

        if ($valid && !(bool) Settings::get('allow_local_login', true)) {
            // Local login disabled — super admins can never be locked out.
            $isSuperAdmin = DB::fetch(
                "SELECT 1 FROM user_role ur JOIN roles r ON r.id = ur.role_id
                 WHERE ur.user_id = ? AND r.slug = 'super_admin'",
                [(int) $user['id']]
            ) !== null;
            if (!$isSuperAdmin) {
                $valid = false;
            }
        }

        if (!$valid) {
            LoginThrottle::record($email, $ip, false);
            Audit::log('auth.login_failed', 'user', null, ['email' => $email]);
            flash('error', 'Invalid credentials.');
            Flash::keepInput();
            redirect('login');
        }

        LoginThrottle::record($email, $ip, true);
        Auth::login($user, $remember);
        Audit::log('auth.login', 'user', (int) $user['id']);

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_ARGON2ID)) {
            DB::update('users', ['password_hash' => password_hash($password, PASSWORD_ARGON2ID)], 'id = ?', [(int) $user['id']]);
        }

        if ((int) $user['must_change_password'] === 1) {
            redirect('password/change');
        }
        $intended = $_SESSION['intended'] ?? null;
        unset($_SESSION['intended']);
        if (is_string($intended) && str_starts_with($intended, '/') && !str_starts_with($intended, '//')) {
            redirect($intended);
        }
        redirect('/');
    }

    public function logout(): void
    {
        Audit::log('auth.logout', 'user', Auth::id());
        Auth::logout();
        redirect('login');
    }
}
