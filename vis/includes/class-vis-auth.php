<?php

if (! defined('ABSPATH')) {
    exit;
}

class VIS_Auth
{
    private const SESSION_KEY = 'vis_external_user';

    public static function bootstrap_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(string $login, string $password): bool
    {
        $db = VIS_External_DB::create_connection();
        if (! $db instanceof wpdb) {
            return false;
        }

        $users_table = VIS_External_DB::prefixed_table('users');
        $clubs_table = VIS_External_DB::prefixed_table('clubs');

        $user = $db->get_row(
            $db->prepare(
                "SELECT u.id, u.login, u.password_hash, u.display_name, u.email, c.name AS club_name
                 FROM {$users_table} u
                 LEFT JOIN {$clubs_table} c ON c.id = u.club_id
                 WHERE u.login = %s AND u.status = 'active' LIMIT 1",
                $login
            ),
            ARRAY_A
        );

        if (! is_array($user)) {
            return false;
        }

        $hash = (string) ($user['password_hash'] ?? '');
        if ($hash === '' || ! password_verify($password, $hash)) {
            return false;
        }

        $_SESSION[self::SESSION_KEY] = [
            'id' => (int) $user['id'],
            'login' => (string) $user['login'],
            'display_name' => (string) $user['display_name'],
            'email' => (string) ($user['email'] ?? ''),
            'club_name' => (string) ($user['club_name'] ?? ''),
        ];

        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public static function current_user(): ?array
    {
        $user = $_SESSION[self::SESSION_KEY] ?? null;

        return is_array($user) ? $user : null;
    }
}
