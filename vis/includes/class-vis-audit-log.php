<?php

if (! defined('ABSPATH')) {
    exit;
}

class VIS_Audit_Log
{
    public static function write(string $action, string $entity_type, int $entity_id, array $details = []): void
    {
        $db = VIS_External_DB::create_connection();
        if (! $db instanceof wpdb) {
            return;
        }

        $table = VIS_External_DB::prefixed_table('audit_log');
        $actor_wp_user_id = get_current_user_id();

        $db->insert(
            $table,
            [
                'actor_wp_user_id' => $actor_wp_user_id > 0 ? $actor_wp_user_id : null,
                'action' => sanitize_key($action),
                'entity_type' => sanitize_key($entity_type),
                'entity_id' => $entity_id,
                'details_json' => wp_json_encode($details),
            ],
            ['%d', '%s', '%s', '%d', '%s']
        );
    }

    public static function register_admin_page(): void
    {
        add_submenu_page(
            'options-general.php',
            __('VIS Audit-Log', 'vis'),
            __('VIS Audit-Log', 'vis'),
            'manage_options',
            'vis-audit-log',
            [self::class, 'render_admin_page']
        );
    }

    public static function render_admin_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $db = VIS_External_DB::create_connection();
        if (! $db instanceof wpdb) {
            echo '<div class="wrap"><h1>' . esc_html__('VIS Audit-Log', 'vis') . '</h1><p>'
                . esc_html__('Keine Verbindung zur externen Datenbank. Bitte VIS-Einstellungen prüfen.', 'vis') . '</p></div>';
            return;
        }

        $table = VIS_External_DB::prefixed_table('audit_log');
        $limit = 200;
        $rows = $db->get_results(
            $db->prepare("SELECT id, actor_wp_user_id, action, entity_type, entity_id, details_json, created_at FROM {$table} ORDER BY id DESC LIMIT %d", $limit),
            ARRAY_A
        );
        $rows = is_array($rows) ? $rows : [];
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('VIS Audit-Log', 'vis'); ?></h1>
            <p><?php echo esc_html(sprintf(__('Es werden die letzten %d Einträge angezeigt.', 'vis'), $limit)); ?></p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php esc_html_e('Zeitpunkt', 'vis'); ?></th>
                        <th><?php esc_html_e('WP-Admin', 'vis'); ?></th>
                        <th><?php esc_html_e('Aktion', 'vis'); ?></th>
                        <th><?php esc_html_e('Entität', 'vis'); ?></th>
                        <th><?php esc_html_e('Details', 'vis'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows === []) : ?>
                        <tr><td colspan="6"><?php esc_html_e('Keine Audit-Einträge vorhanden.', 'vis'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($rows as $row) : ?>
                            <tr>
                                <td><?php echo esc_html((string) $row['id']); ?></td>
                                <td><?php echo esc_html((string) $row['created_at']); ?></td>
                                <td><?php echo esc_html((string) ($row['actor_wp_user_id'] ?? '')); ?></td>
                                <td><?php echo esc_html((string) $row['action']); ?></td>
                                <td><?php echo esc_html((string) $row['entity_type'] . '#' . (string) $row['entity_id']); ?></td>
                                <td><code><?php echo esc_html((string) $row['details_json']); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
