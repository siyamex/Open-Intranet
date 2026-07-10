<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\RememberToken;
use App\Core\Settings;
use App\Core\Validator;
use App\Core\View;

final class PasswordController
{
    public function forgotForm(): void
    {
        View::render('auth/forgot', ['title' => 'Reset password'], 'auth');
    }

    public function sendReset(): void
    {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $generic = 'If that email address belongs to an account, a reset link is on its way.';
        $v = new Validator($_POST, ['email' => 'required|email']);
        if ($v->fails()) {
            flash('error', 'Please enter a valid email address.');
            redirect('password/forgot');
        }

        $user = DB::fetch("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
        if ($user !== null && $user['password_hash'] !== null) {
            $token = bin2hex(random_bytes(32));
            DB::run(
                'INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), expires_at = VALUES(expires_at), created_at = CURRENT_TIMESTAMP',
                [$email, hash('sha256', $token), date('Y-m-d H:i:s', time() + 3600)]
            );
            $link = base_url('password/reset/' . $token . '?email=' . rawurlencode($email));
            $site = (string) Settings::get('site_name', 'OpenIntranet');
            Mailer::send(
                $email,
                "Reset your {$site} password",
                '<p>Hello ' . e((string) $user['name']) . ',</p>'
                . '<p>We received a request to reset your password. This link is valid for 1 hour:</p>'
                . '<p><a href="' . e($link) . '">' . e($link) . '</a></p>'
                . '<p>If you did not request this, you can safely ignore this email.</p>'
            );
            Audit::log('auth.password_reset_requested', 'user', (int) $user['id']);
        }
        flash('success', $generic);
        redirect('login');
    }

    public function resetForm(string $token): void
    {
        View::render('auth/reset', [
            'title' => 'Choose a new password',
            'token' => $token,
            'email' => (string) ($_GET['email'] ?? ''),
        ], 'auth');
    }

    public function doReset(): void
    {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $token = (string) ($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $row = DB::fetch('SELECT * FROM password_resets WHERE email = ?', [$email]);
        $validToken = $row !== null
            && strtotime((string) $row['expires_at']) > time()
            && hash_equals((string) $row['token_hash'], hash('sha256', $token));
        if (!$validToken) {
            flash('error', 'This reset link is invalid or has expired. Please request a new one.');
            redirect('password/forgot');
        }

        $error = self::passwordPolicyError($password, (string) ($_POST['password_confirmation'] ?? ''));
        if ($error !== null) {
            flash('error', $error);
            redirect('password/reset/' . rawurlencode($token) . '?email=' . rawurlencode($email));
        }

        $user = DB::fetch("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
        if ($user === null) {
            flash('error', 'This reset link is invalid or has expired.');
            redirect('password/forgot');
        }

        DB::update('users', [
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
            'must_change_password' => 0,
        ], 'id = ?', [(int) $user['id']]);
        DB::delete('password_resets', 'email = ?', [$email]);
        RememberToken::clearAllFor((int) $user['id']);
        Audit::log('auth.password_reset', 'user', (int) $user['id'], [], (int) $user['id']);

        flash('success', 'Your password has been updated — you can sign in now.');
        redirect('login');
    }

    public function changeForm(): void
    {
        View::render('auth/change-password', [
            'title' => 'Change password',
            'forced' => (int) (Auth::user()['must_change_password'] ?? 0) === 1,
            'hasPassword' => Auth::user()['password_hash'] !== null,
        ], 'auth');
    }

    public function change(): void
    {
        $user = Auth::user();
        $current = (string) ($_POST['current_password'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($user['password_hash'] !== null && !password_verify($current, (string) $user['password_hash'])) {
            flash('error', 'Your current password is incorrect.');
            redirect('password/change');
        }

        $error = self::passwordPolicyError($password, (string) ($_POST['password_confirmation'] ?? ''));
        if ($error !== null) {
            flash('error', $error);
            redirect('password/change');
        }

        DB::update('users', [
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
            'must_change_password' => 0,
        ], 'id = ?', [(int) $user['id']]);
        RememberToken::clearAllFor((int) $user['id']);
        Audit::log('auth.password_changed', 'user', (int) $user['id']);
        Auth::refresh();

        flash('success', 'Password updated.');
        redirect('/');
    }

    /**
     * Enforce the password policy: settings-driven minimum length,
     * confirmation match, and the bundled common-passwords blocklist.
     */
    public static function passwordPolicyError(string $password, string $confirmation): ?string
    {
        $min = (int) Settings::get('password_min_length', 10);
        if (mb_strlen($password) < $min) {
            return "Password must be at least {$min} characters long.";
        }
        if ($password !== $confirmation) {
            return 'Password confirmation does not match.';
        }
        $common = (array) config('common-passwords', []);
        if (in_array(strtolower($password), $common, true)) {
            return 'That password is too common — please choose something harder to guess.';
        }
        return null;
    }
}
