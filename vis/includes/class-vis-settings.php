<?php

if (! defined('ABSPATH')) {
    exit;
}

class VIS_Settings
{
    public static function register_settings(): void
    {
        register_setting('vis_external_db', VIS_External_DB::OPTION_DB_CONFIG, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize_external_db_config'],
            'default' => [],
        ]);
    }

    public static function sanitize_external_db_config($config): array
    {
        if (! is_array($config)) {
            return [];
        }

        return [
            'host' => sanitize_text_field((string) ($config['host'] ?? '')),
            'port' => max(1, (int) ($config['port'] ?? 3306)),
            'database' => sanitize_text_field((string) ($config['database'] ?? '')),
            'user' => sanitize_text_field((string) ($config['user'] ?? '')),
            'password' => (string) ($config['password'] ?? ''),
            'charset' => sanitize_text_field((string) ($config['charset'] ?? 'utf8mb4')),
            'collate' => sanitize_text_field((string) ($config['collate'] ?? '')),
            'prefix' => sanitize_key((string) ($config['prefix'] ?? 'vis_')),
        ];
    }

    public static function register_admin_page(): void
    {
        add_options_page(
            __('VIS Einstellungen', 'vis'),
            __('VIS Einstellungen', 'vis'),
            'manage_options',
            'vis-settings',
            [self::class, 'render_admin_page']
        );
    }

    public static function render_admin_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $config = VIS_External_DB::get_config();
        $action_result = null;

        if (
            isset($_POST['vis_nonce'])
            && wp_verify_nonce(sanitize_text_field((string) $_POST['vis_nonce']), 'vis_settings_action')
            && isset($_POST['vis_action'])
        ) {
            $action = sanitize_key((string) $_POST['vis_action']);

            if ($action === 'test_connection') {
                $action_result = VIS_External_DB::test_connection();
            }

            if ($action === 'setup_schema') {
                $action_result = VIS_Schema_Manager::migrate();
            }
        }


        $last_migration = VIS_Schema_Manager::get_last_migration_info();
        $installed_version = VIS_Schema_Manager::get_installed_version();
        $target_version = VIS_Schema_Manager::SCHEMA_VERSION;
        $needs_migration = VIS_Schema_Manager::needs_migration();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('VIS – Externe Datenbank', 'vis'); ?></h1>
            <p><?php esc_html_e('VIS nutzt eine externe MySQL-Datenbank für Benutzer, Rechte, Module und Fachdaten.', 'vis'); ?></p>

            <?php if (is_array($action_result)) : ?>
                <div class="notice <?php echo $action_result['ok'] ? 'notice-success' : 'notice-error'; ?>"><p><?php echo esc_html((string) $action_result['message']); ?></p></div>
            <?php endif; ?>

            <h2><?php esc_html_e('1) Verbindungsdaten', 'vis'); ?></h2>
            <form method="post" action="options.php">
                <?php settings_fields('vis_external_db'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                    <tr><th scope="row"><label for="vis-db-host">Host</label></th><td><input id="vis-db-host" name="<?php echo esc_attr(VIS_External_DB::OPTION_DB_CONFIG); ?>[host]" value="<?php echo esc_attr($config['host']); ?>" class="regular-text" /></td></tr>
                    <tr><th scope="row"><label for="vis-db-port">Port</label></th><td><input id="vis-db-port" type="number" name="<?php echo esc_attr(VIS_External_DB::OPTION_DB_CONFIG); ?>[port]" value="<?php echo esc_attr((string) $config['port']); ?>" class="small-text" /></td></tr>
                    <tr><th scope="row"><label for="vis-db-name">Datenbank</label></th><td><input id="vis-db-name" name="<?php echo esc_attr(VIS_External_DB::OPTION_DB_CONFIG); ?>[database]" value="<?php echo esc_attr($config['database']); ?>" class="regular-text" /></td></tr>
                    <tr><th scope="row"><label for="vis-db-user">Benutzer</label></th><td><input id="vis-db-user" name="<?php echo esc_attr(VIS_External_DB::OPTION_DB_CONFIG); ?>[user]" value="<?php echo esc_attr($config['user']); ?>" class="regular-text" /></td></tr>
                    <tr><th scope="row"><label for="vis-db-password">Passwort</label></th><td><input id="vis-db-password" type="password" name="<?php echo esc_attr(VIS_External_DB::OPTION_DB_CONFIG); ?>[password]" value="<?php echo esc_attr($config['password']); ?>" class="regular-text" /></td></tr>
                    <tr><th scope="row"><label for="vis-db-charset">Charset</label></th><td><input id="vis-db-charset" name="<?php echo esc_attr(VIS_External_DB::OPTION_DB_CONFIG); ?>[charset]" value="<?php echo esc_attr($config['charset']); ?>" class="regular-text" /></td></tr>
                    <tr><th scope="row"><label for="vis-db-collate">Collate</label></th><td><input id="vis-db-collate" name="<?php echo esc_attr(VIS_External_DB::OPTION_DB_CONFIG); ?>[collate]" value="<?php echo esc_attr($config['collate']); ?>" class="regular-text" /></td></tr>
                    <tr><th scope="row"><label for="vis-db-prefix">Tabellenprefix</label></th><td><input id="vis-db-prefix" name="<?php echo esc_attr(VIS_External_DB::OPTION_DB_CONFIG); ?>[prefix]" value="<?php echo esc_attr($config['prefix']); ?>" class="regular-text" /></td></tr>
                    </tbody>
                </table>
                <?php submit_button(__('Einstellungen speichern', 'vis')); ?>
            </form>

            <h2><?php esc_html_e('2) Verbindung testen', 'vis'); ?></h2>
            <form method="post">
                <input type="hidden" name="vis_action" value="test_connection" />
                <input type="hidden" name="vis_nonce" value="<?php echo esc_attr(wp_create_nonce('vis_settings_action')); ?>" />
                <?php submit_button(__('Externe DB-Verbindung testen', 'vis'), 'secondary', 'submit', false); ?>
            </form>

            <h2><?php esc_html_e('3) Datenbank einrichten/aktualisieren', 'vis'); ?></h2>
            <p>
                <?php echo esc_html(sprintf(__('Installierte Schema-Version: %1$s | Ziel-Version: %2$s', 'vis'), $installed_version, $target_version)); ?>
            </p>
            <p>
                <?php echo $needs_migration
                    ? esc_html__('Es ist eine Einrichtung/Aktualisierung erforderlich.', 'vis')
                    : esc_html__('Schema ist auf dem aktuellen Stand.', 'vis'); ?>
            </p>
            <p>
                <?php
                if ($last_migration['ran_at'] !== '') {
                    echo esc_html(sprintf(
                        __('Letzte Migration: %1$s | Ergebnis: %2$s', 'vis'),
                        $last_migration['ran_at'],
                        $last_migration['ok'] ? __('Erfolgreich', 'vis') : __('Fehlgeschlagen', 'vis')
                    ));
                }
                ?>
            </p>
            <?php if ($last_migration['message'] !== '') : ?>
                <p><em><?php echo esc_html($last_migration['message']); ?></em></p>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="vis_action" value="setup_schema" />
                <input type="hidden" name="vis_nonce" value="<?php echo esc_attr(wp_create_nonce('vis_settings_action')); ?>" />
                <?php submit_button(__('Schema einrichten/aktualisieren', 'vis'), 'primary', 'submit', false); ?>
            </form>
        </div>
        <?php
    }
}
