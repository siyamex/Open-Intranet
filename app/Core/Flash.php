<?php

declare(strict_types=1);

namespace App\Core;

final class Flash
{
    public static function set(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * @return array<int, array{type: string, message: string}>
     */
    public static function pull(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }

    /**
     * Keep submitted input for the next request (used by old()).
     */
    public static function keepInput(?array $input = null): void
    {
        $input ??= $_POST;
        unset($input['password'], $input['password_confirmation'], $input['current_password'], $input['_token'], $input['_method']);
        $_SESSION['_old_next'] = $input;
    }

    /**
     * Rotate flashed input at the start of each request.
     */
    public static function ageInput(): void
    {
        $_SESSION['_old'] = $_SESSION['_old_next'] ?? [];
        unset($_SESSION['_old_next']);
    }

    public static function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}
