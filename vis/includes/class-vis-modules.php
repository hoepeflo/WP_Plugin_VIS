<?php

if (! defined('ABSPATH')) {
    exit;
}

class VIS_Modules
{
    public static function get_enabled_modules_for_external_user(int $external_user_id): array
    {
        $db = VIS_External_DB::create_connection();
        if (! $db instanceof wpdb) {
            return [];
        }

        $modules_table = VIS_External_DB::prefixed_table('modules');
        $assignments_table = VIS_External_DB::prefixed_table('user_modules');

        $rows = $db->get_results(
            $db->prepare(
                "SELECT m.module_key, m.label, m.description, m.required_permission
                 FROM {$modules_table} m
                 INNER JOIN {$assignments_table} um ON um.module_id = m.id
                 WHERE um.user_id = %d AND um.is_enabled = 1 AND m.is_enabled = 1
                 ORDER BY m.sort_order ASC, m.label ASC",
                $external_user_id
            ),
            ARRAY_A
        );

        if (! is_array($rows)) {
            return [];
        }

        $filtered_modules = [];

        foreach ($rows as $row) {
            if (! VIS_Access::can_access_module($external_user_id, $row)) {
                continue;
            }

            $module_key = sanitize_key((string) ($row['module_key'] ?? ''));
            $row['module_url'] = add_query_arg('vis_module', $module_key, get_permalink());
            $filtered_modules[] = $row;
        }

        return $filtered_modules;
    }

    public static function render_module(string $module_key, array $external_user): string
    {
        if ($module_key === 'bildungsportal') {
            return VIS_Bildungsportal::render($external_user);
        }

        return '<div class="vis-module-placeholder"><h3>'
            . esc_html(sprintf(__('Modul: %s', 'vis'), $module_key))
            . '</h3><p>'
            . esc_html__('Dieses Modul ist freigeschaltet. Die fachliche Integration wird im nächsten Schritt ergänzt.', 'vis')
            . '</p></div>';
    }

    public static function get_migration_sources(): array
    {
        return [
            [
                'module_key' => 'bildungsportal',
                'label' => 'Bildungsportal',
                'migration_status' => 'in_progress',
                'source_type' => 'github_repo',
                'note' => __('Funktionalität und Datenbankschema aus bestehendem Repository übernehmen, nicht den Code unverändert kopieren.', 'vis'),
            ],
            [
                'module_key' => 'kidscup',
                'label' => 'KidsCup',
                'migration_status' => 'planned',
                'source_type' => 'github_repo',
                'note' => __('Funktionalität und Datenbankschema aus bestehendem Repository übernehmen, nicht den Code unverändert kopieren.', 'vis'),
            ],
        ];
    }
}
