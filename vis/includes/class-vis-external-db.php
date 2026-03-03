<?php

if (! defined('ABSPATH')) {
    exit;
}

class VIS_External_DB
{
    public const OPTION_DB_CONFIG = 'vis_external_db_config';

    public static function is_configured(): bool
    {
        $config = self::get_config();

        return $config['host'] !== ''
            && $config['database'] !== ''
            && $config['user'] !== '';
    }

    public static function get_config(): array
    {
        $config = get_option(self::OPTION_DB_CONFIG, []);

        return [
            'host' => isset($config['host']) ? sanitize_text_field((string) $config['host']) : '',
            'port' => isset($config['port']) ? (int) $config['port'] : 3306,
            'database' => isset($config['database']) ? sanitize_text_field((string) $config['database']) : '',
            'user' => isset($config['user']) ? sanitize_text_field((string) $config['user']) : '',
            'password' => isset($config['password']) ? (string) $config['password'] : '',
            'charset' => isset($config['charset']) ? sanitize_text_field((string) $config['charset']) : 'utf8mb4',
            'collate' => isset($config['collate']) ? sanitize_text_field((string) $config['collate']) : '',
            'prefix' => isset($config['prefix']) ? sanitize_key((string) $config['prefix']) : 'vis_',
        ];
    }

    public static function create_connection(): ?wpdb
    {
        if (! self::is_configured()) {
            return null;
        }

        $config = self::get_config();
        $host = $config['host'] . ':' . (string) $config['port'];

        $db = new wpdb($config['user'], $config['password'], $config['database'], $host);
        $db->set_charset($db->dbh, $config['charset'], $config['collate']);
        $db->show_errors = false;
        $db->suppress_errors(true);

        if (! empty($db->error)) {
            return null;
        }

        return $db;
    }

    public static function test_connection(): array
    {
        $db = self::create_connection();

        if (! $db instanceof wpdb) {
            return [
                'ok' => false,
                'message' => __('Verbindung zur externen Datenbank fehlgeschlagen.', 'vis'),
            ];
        }

        $result = $db->get_var('SELECT 1');

        if ((string) $result !== '1') {
            return [
                'ok' => false,
                'message' => __('Verbindung besteht, aber Testabfrage fehlgeschlagen.', 'vis'),
            ];
        }

        return [
            'ok' => true,
            'message' => __('Verbindung zur externen Datenbank erfolgreich.', 'vis'),
        ];
    }

    public static function prefixed_table(string $table): string
    {
        $config = self::get_config();
        return $config['prefix'] . $table;
    }
}
