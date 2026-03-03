<?php

if (! defined('ABSPATH')) {
    exit;
}

class VIS_Schema_Manager
{
    public const OPTION_SCHEMA_VERSION = 'vis_external_schema_version';
    public const OPTION_LAST_MIGRATION = 'vis_external_schema_last_migration';
    public const SCHEMA_VERSION = '1.2.0';

    public static function get_installed_version(): string
    {
        $version = get_option(self::OPTION_SCHEMA_VERSION, '0.0.0');
        return is_string($version) ? $version : '0.0.0';
    }

    public static function needs_migration(): bool
    {
        return version_compare(self::get_installed_version(), self::SCHEMA_VERSION, '<');
    }

    public static function maybe_auto_migrate(): void
    {
        if (! VIS_External_DB::is_configured() || ! self::needs_migration()) {
            return;
        }

        self::migrate();
    }

    public static function migrate(): array
    {
        $db = VIS_External_DB::create_connection();

        if (! $db instanceof wpdb) {
            return self::store_migration_result(false, __('Migration fehlgeschlagen: keine Verbindung zur externen Datenbank.', 'vis'));
        }

        $sql_file = self::get_schema_sql_file();
        if (! file_exists($sql_file)) {
            return self::store_migration_result(false, __('Migration fehlgeschlagen: SQL-Schema-Datei nicht gefunden.', 'vis'));
        }

        $raw_sql = file_get_contents($sql_file);
        if (! is_string($raw_sql) || trim($raw_sql) === '') {
            return self::store_migration_result(false, __('Migration fehlgeschlagen: SQL-Schema-Datei ist leer.', 'vis'));
        }

        $prefix = VIS_External_DB::get_config()['prefix'];
        $sql = str_replace('vis_', $prefix, $raw_sql);

        $statements = self::split_sql_statements($sql);
        if ($statements === []) {
            return self::store_migration_result(false, __('Migration fehlgeschlagen: keine SQL-Statements gefunden.', 'vis'));
        }

        $db->query('START TRANSACTION');

        foreach ($statements as $statement) {
            $result = $db->query($statement);
            if ($result === false) {
                $db->query('ROLLBACK');

                return self::store_migration_result(
                    false,
                    sprintf(
                        /* translators: %s: database error */
                        __('Migration fehlgeschlagen: %s', 'vis'),
                        (string) $db->last_error
                    )
                );
            }
        }

        $db->query('COMMIT');
        update_option(self::OPTION_SCHEMA_VERSION, self::SCHEMA_VERSION);

        return self::store_migration_result(true, __('Datenbankschema erfolgreich eingerichtet/aktualisiert.', 'vis'));
    }

    public static function get_last_migration_info(): array
    {
        $info = get_option(self::OPTION_LAST_MIGRATION, []);

        return [
            'ok' => isset($info['ok']) ? (bool) $info['ok'] : null,
            'message' => isset($info['message']) ? (string) $info['message'] : '',
            'ran_at' => isset($info['ran_at']) ? (string) $info['ran_at'] : '',
        ];
    }

    private static function get_schema_sql_file(): string
    {
        $plugin_root = dirname(plugin_dir_path(__DIR__));
        return $plugin_root . '/docs/sql/vis_external_schema.sql';
    }

    private static function store_migration_result(bool $ok, string $message): array
    {
        $result = [
            'ok' => $ok,
            'message' => $message,
            'ran_at' => gmdate('c'),
        ];

        update_option(self::OPTION_LAST_MIGRATION, $result);

        return $result;
    }

    private static function split_sql_statements(string $sql): array
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        $sql = is_string($sql) ? $sql : '';

        $statements = [];
        $buffer = '';
        $in_single_quote = false;
        $in_double_quote = false;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $prev = $i > 0 ? $sql[$i - 1] : '';

            if ($char === "'" && $prev !== '\\' && ! $in_double_quote) {
                $in_single_quote = ! $in_single_quote;
            }

            if ($char === '"' && $prev !== '\\' && ! $in_single_quote) {
                $in_double_quote = ! $in_double_quote;
            }

            if ($char === ';' && ! $in_single_quote && ! $in_double_quote) {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $last_statement = trim($buffer);
        if ($last_statement !== '') {
            $statements[] = $last_statement;
        }

        return $statements;
    }
}
