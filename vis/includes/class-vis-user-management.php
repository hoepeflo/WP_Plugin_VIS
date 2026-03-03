<?php

if (! defined('ABSPATH')) {
    exit;
}

class VIS_User_Management
{
    public static function register_admin_page(): void
    {
        add_submenu_page(
            'options-general.php',
            __('VIS Benutzerrechte', 'vis'),
            __('VIS Benutzerrechte', 'vis'),
            'manage_options',
            'vis-user-management',
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
            echo '<div class="wrap"><h1>' . esc_html__('VIS Benutzerrechte', 'vis') . '</h1><p>'
                . esc_html__('Keine Verbindung zur externen Datenbank. Bitte VIS-Einstellungen prüfen.', 'vis') . '</p></div>';
            return;
        }

        $action_message = '';

        if (
            isset($_POST['vis_user_management_nonce'])
            && wp_verify_nonce(sanitize_text_field((string) $_POST['vis_user_management_nonce']), 'vis_user_management_action')
            && isset($_POST['vis_action'])
        ) {
            $action = sanitize_key((string) $_POST['vis_action']);

            if ($action === 'save_user_access') {
                $user_id = isset($_POST['external_user_id']) ? (int) $_POST['external_user_id'] : 0;
                if ($user_id > 0) {
                    $permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? array_map('sanitize_key', $_POST['permissions']) : [];
                    $modules = isset($_POST['modules']) && is_array($_POST['modules']) ? array_map('sanitize_key', $_POST['modules']) : [];
                    self::save_user_access($db, $user_id, $permissions, $modules);
                    VIS_Audit_Log::write('save_user_access', 'user', $user_id, [
                        'permissions' => $permissions,
                        'modules' => $modules,
                    ]);
                    $action_message = __('Benutzerrechte und Modulfreigaben wurden gespeichert.', 'vis');
                }
            }

            if ($action === 'bulk_assign_module') {
                $module_key = isset($_POST['bulk_module_key']) ? sanitize_key((string) $_POST['bulk_module_key']) : '';
                $user_ids = isset($_POST['bulk_user_ids']) && is_array($_POST['bulk_user_ids']) ? array_map('intval', $_POST['bulk_user_ids']) : [];
                if ($module_key !== '' && $user_ids !== []) {
                    $affected = self::bulk_assign_module($db, $module_key, $user_ids);
                    VIS_Audit_Log::write('bulk_assign_module', 'module', 0, [
                        'module_key' => $module_key,
                        'user_ids' => $user_ids,
                        'affected' => $affected,
                    ]);
                    $action_message = sprintf(__('Modul wurde für %d Benutzer freigeschaltet.', 'vis'), $affected);
                }
            }
        }

        $users = self::get_users($db);
        $permissions = self::get_permissions($db);
        $modules = self::get_modules($db);

        $selected_user_id = isset($_GET['external_user_id']) ? (int) $_GET['external_user_id'] : 0;
        if ($selected_user_id === 0 && isset($_POST['external_user_id'])) {
            $selected_user_id = (int) $_POST['external_user_id'];
        }

        $selected_user_permissions = $selected_user_id > 0 ? self::get_user_direct_permissions($db, $selected_user_id) : [];
        $selected_user_modules = $selected_user_id > 0 ? self::get_user_enabled_modules($db, $selected_user_id) : [];
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('VIS Benutzerrechte', 'vis'); ?></h1>
            <p><?php esc_html_e('Verwalten Sie direkte Benutzerrechte und Modulfreigaben in der externen VIS-Datenbank.', 'vis'); ?></p>

            <?php if ($action_message !== '') : ?>
                <div class="notice notice-success"><p><?php echo esc_html($action_message); ?></p></div>
            <?php endif; ?>

            <form method="get" style="margin-bottom:1rem;">
                <input type="hidden" name="page" value="vis-user-management" />
                <label for="external_user_id"><strong><?php esc_html_e('Benutzer auswählen', 'vis'); ?></strong></label>
                <select name="external_user_id" id="external_user_id">
                    <option value="0"><?php esc_html_e('Bitte auswählen', 'vis'); ?></option>
                    <?php foreach ($users as $user) : ?>
                        <option value="<?php echo esc_attr((string) $user['id']); ?>" <?php selected($selected_user_id === (int) $user['id']); ?>>
                            <?php echo esc_html((string) $user['display_name'] . ' (' . (string) $user['login'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php submit_button(__('Laden', 'vis'), 'secondary', 'submit', false); ?>
            </form>

            <?php if ($selected_user_id > 0) : ?>
                <form method="post">
                    <input type="hidden" name="vis_action" value="save_user_access" />
                    <input type="hidden" name="external_user_id" value="<?php echo esc_attr((string) $selected_user_id); ?>" />
                    <input type="hidden" name="vis_user_management_nonce" value="<?php echo esc_attr(wp_create_nonce('vis_user_management_action')); ?>" />

                    <h2><?php esc_html_e('Direkte Berechtigungen', 'vis'); ?></h2>
                    <?php if ($permissions === []) : ?>
                        <p><?php esc_html_e('Keine Berechtigungen in der externen DB gefunden.', 'vis'); ?></p>
                    <?php else : ?>
                        <fieldset>
                            <?php foreach ($permissions as $permission) : ?>
                                <label style="display:block; margin:0.25rem 0;">
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="<?php echo esc_attr((string) $permission['permission_key']); ?>"
                                        <?php checked(in_array((string) $permission['permission_key'], $selected_user_permissions, true)); ?>
                                    />
                                    <?php echo esc_html((string) $permission['label'] . ' (' . (string) $permission['permission_key'] . ')'); ?>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    <?php endif; ?>

                    <h2><?php esc_html_e('Modulfreigaben', 'vis'); ?></h2>
                    <?php if ($modules === []) : ?>
                        <p><?php esc_html_e('Keine Module in der externen DB gefunden.', 'vis'); ?></p>
                    <?php else : ?>
                        <fieldset>
                            <?php foreach ($modules as $module) : ?>
                                <label style="display:block; margin:0.25rem 0;">
                                    <input
                                        type="checkbox"
                                        name="modules[]"
                                        value="<?php echo esc_attr((string) $module['module_key']); ?>"
                                        <?php checked(in_array((string) $module['module_key'], $selected_user_modules, true)); ?>
                                    />
                                    <?php echo esc_html((string) $module['label'] . ' (' . (string) $module['module_key'] . ')'); ?>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    <?php endif; ?>

                    <?php submit_button(__('Benutzerzugriffe speichern', 'vis')); ?>
                </form>
            <?php endif; ?>

            <hr />
            <h2><?php esc_html_e('Bulk-Workflow: Modul mehreren Benutzern zuweisen', 'vis'); ?></h2>
            <form method="post">
                <input type="hidden" name="vis_action" value="bulk_assign_module" />
                <input type="hidden" name="vis_user_management_nonce" value="<?php echo esc_attr(wp_create_nonce('vis_user_management_action')); ?>" />
                <p>
                    <label for="bulk_module_key"><strong><?php esc_html_e('Modul', 'vis'); ?></strong></label><br />
                    <select id="bulk_module_key" name="bulk_module_key" required>
                        <option value=""><?php esc_html_e('Bitte auswählen', 'vis'); ?></option>
                        <?php foreach ($modules as $module) : ?>
                            <option value="<?php echo esc_attr((string) $module['module_key']); ?>"><?php echo esc_html((string) $module['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p><strong><?php esc_html_e('Benutzer', 'vis'); ?></strong></p>
                <fieldset style="max-height:240px; overflow:auto; border:1px solid #ccd0d4; padding:0.75rem;">
                    <?php foreach ($users as $user) : ?>
                        <label style="display:block; margin:0.25rem 0;">
                            <input type="checkbox" name="bulk_user_ids[]" value="<?php echo esc_attr((string) $user['id']); ?>" />
                            <?php echo esc_html((string) $user['display_name'] . ' (' . (string) $user['login'] . ')'); ?>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <?php submit_button(__('Bulk-Freigabe ausführen', 'vis'), 'secondary'); ?>
            </form>
        </div>
        <?php
    }

    private static function get_users(wpdb $db): array
    {
        $users_table = VIS_External_DB::prefixed_table('users');
        $rows = $db->get_results("SELECT id, login, display_name FROM {$users_table} WHERE status = 'active' ORDER BY display_name ASC", ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    private static function get_permissions(wpdb $db): array
    {
        $permissions_table = VIS_External_DB::prefixed_table('permissions');
        $rows = $db->get_results("SELECT permission_key, label FROM {$permissions_table} WHERE is_active = 1 ORDER BY label ASC", ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    private static function get_modules(wpdb $db): array
    {
        $modules_table = VIS_External_DB::prefixed_table('modules');
        $rows = $db->get_results("SELECT id, module_key, label FROM {$modules_table} WHERE is_enabled = 1 ORDER BY sort_order ASC, label ASC", ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    private static function get_user_direct_permissions(wpdb $db, int $user_id): array
    {
        $user_permissions_table = VIS_External_DB::prefixed_table('user_permissions');
        $rows = $db->get_col($db->prepare("SELECT permission_key FROM {$user_permissions_table} WHERE user_id = %d AND is_granted = 1", $user_id));

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter(array_map('sanitize_key', $rows)));
    }

    private static function get_user_enabled_modules(wpdb $db, int $user_id): array
    {
        $modules_table = VIS_External_DB::prefixed_table('modules');
        $user_modules_table = VIS_External_DB::prefixed_table('user_modules');

        $rows = $db->get_col($db->prepare(
            "SELECT m.module_key
             FROM {$modules_table} m
             INNER JOIN {$user_modules_table} um ON um.module_id = m.id
             WHERE um.user_id = %d AND um.is_enabled = 1",
            $user_id
        ));

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter(array_map('sanitize_key', $rows)));
    }

    private static function save_user_access(wpdb $db, int $user_id, array $permissions, array $modules): void
    {
        $user_permissions_table = VIS_External_DB::prefixed_table('user_permissions');
        $permissions_table = VIS_External_DB::prefixed_table('permissions');
        $modules_table = VIS_External_DB::prefixed_table('modules');
        $user_modules_table = VIS_External_DB::prefixed_table('user_modules');

        $allowed_permissions = $db->get_col("SELECT permission_key FROM {$permissions_table} WHERE is_active = 1");
        $allowed_permissions = is_array($allowed_permissions) ? array_map('sanitize_key', $allowed_permissions) : [];
        $permissions = array_values(array_intersect($allowed_permissions, $permissions));

        $allowed_modules = $db->get_results("SELECT id, module_key FROM {$modules_table} WHERE is_enabled = 1", ARRAY_A);
        $module_map = [];
        if (is_array($allowed_modules)) {
            foreach ($allowed_modules as $module) {
                $module_map[sanitize_key((string) $module['module_key'])] = (int) $module['id'];
            }
        }

        $db->query($db->prepare("DELETE FROM {$user_permissions_table} WHERE user_id = %d", $user_id));
        foreach ($permissions as $permission_key) {
            $db->insert(
                $user_permissions_table,
                [
                    'user_id' => $user_id,
                    'permission_key' => $permission_key,
                    'is_granted' => 1,
                ],
                ['%d', '%s', '%d']
            );
        }

        $db->query($db->prepare("DELETE FROM {$user_modules_table} WHERE user_id = %d", $user_id));
        foreach ($modules as $module_key) {
            $module_key = sanitize_key($module_key);
            if (! isset($module_map[$module_key])) {
                continue;
            }

            $db->insert(
                $user_modules_table,
                [
                    'user_id' => $user_id,
                    'module_id' => $module_map[$module_key],
                    'is_enabled' => 1,
                ],
                ['%d', '%d', '%d']
            );
        }
    }

    private static function bulk_assign_module(wpdb $db, string $module_key, array $user_ids): int
    {
        $modules_table = VIS_External_DB::prefixed_table('modules');
        $user_modules_table = VIS_External_DB::prefixed_table('user_modules');

        $module_id = (int) $db->get_var($db->prepare("SELECT id FROM {$modules_table} WHERE module_key = %s AND is_enabled = 1 LIMIT 1", $module_key));
        if ($module_id <= 0) {
            return 0;
        }

        $affected = 0;
        foreach ($user_ids as $user_id) {
            if ($user_id <= 0) {
                continue;
            }

            $db->query($db->prepare("DELETE FROM {$user_modules_table} WHERE user_id = %d AND module_id = %d", $user_id, $module_id));
            $inserted = $db->insert(
                $user_modules_table,
                [
                    'user_id' => $user_id,
                    'module_id' => $module_id,
                    'is_enabled' => 1,
                ],
                ['%d', '%d', '%d']
            );

            if ($inserted !== false) {
                $affected++;
            }
        }

        return $affected;
    }
}
