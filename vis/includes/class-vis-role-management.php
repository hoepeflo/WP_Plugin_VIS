<?php

if (! defined('ABSPATH')) {
    exit;
}

class VIS_Role_Management
{
    public static function register_admin_page(): void
    {
        add_submenu_page(
            'options-general.php',
            __('VIS Rollenverwaltung', 'vis'),
            __('VIS Rollenverwaltung', 'vis'),
            'manage_options',
            'vis-role-management',
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
            echo '<div class="wrap"><h1>' . esc_html__('VIS Rollenverwaltung', 'vis') . '</h1><p>'
                . esc_html__('Keine Verbindung zur externen Datenbank. Bitte VIS-Einstellungen prüfen.', 'vis') . '</p></div>';
            return;
        }

        $message = '';

        if (
            isset($_POST['vis_role_management_nonce'])
            && wp_verify_nonce(sanitize_text_field((string) $_POST['vis_role_management_nonce']), 'vis_role_management_action')
            && isset($_POST['vis_action'])
        ) {
            $action = sanitize_key((string) $_POST['vis_action']);

            if ($action === 'create_role') {
                $role_key = isset($_POST['role_key']) ? sanitize_key((string) $_POST['role_key']) : '';
                $label = isset($_POST['label']) ? sanitize_text_field((string) $_POST['label']) : '';
                if ($role_key !== '' && $label !== '') {
                    $new_role_id = self::create_role($db, $role_key, $label);
                    VIS_Audit_Log::write('create_role', 'role', $new_role_id, ['role_key' => $role_key, 'label' => $label]);
                    $message = __('Rolle wurde erstellt.', 'vis');
                }
            }

            if ($action === 'save_role_permissions') {
                $role_id = isset($_POST['role_id']) ? (int) $_POST['role_id'] : 0;
                $permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? array_map('sanitize_key', $_POST['permissions']) : [];
                if ($role_id > 0) {
                    self::save_role_permissions($db, $role_id, $permissions);
                    VIS_Audit_Log::write('save_role_permissions', 'role', $role_id, ['permissions' => $permissions]);
                    $message = __('Rollenberechtigungen wurden gespeichert.', 'vis');
                }
            }

            if ($action === 'save_user_roles') {
                $user_id = isset($_POST['external_user_id']) ? (int) $_POST['external_user_id'] : 0;
                $roles = isset($_POST['roles']) && is_array($_POST['roles']) ? array_map('intval', $_POST['roles']) : [];
                if ($user_id > 0) {
                    self::save_user_roles($db, $user_id, $roles);
                    VIS_Audit_Log::write('save_user_roles', 'user', $user_id, ['role_ids' => $roles]);
                    $message = __('Benutzerrollen wurden gespeichert.', 'vis');
                }
            }

            if ($action === 'bulk_assign_role') {
                $role_id = isset($_POST['bulk_role_id']) ? (int) $_POST['bulk_role_id'] : 0;
                $user_ids = isset($_POST['bulk_user_ids']) && is_array($_POST['bulk_user_ids']) ? array_map('intval', $_POST['bulk_user_ids']) : [];
                if ($role_id > 0 && $user_ids !== []) {
                    $affected = self::bulk_assign_role($db, $role_id, $user_ids);
                    VIS_Audit_Log::write('bulk_assign_role', 'role', $role_id, ['user_ids' => $user_ids, 'affected' => $affected]);
                    $message = sprintf(__('Rolle wurde für %d Benutzer zugewiesen.', 'vis'), $affected);
                }
            }
        }

        $roles = self::get_roles($db);
        $permissions = self::get_permissions($db);
        $users = self::get_users($db);

        $selected_role_id = isset($_GET['role_id']) ? (int) $_GET['role_id'] : 0;
        if ($selected_role_id === 0 && isset($_POST['role_id'])) {
            $selected_role_id = (int) $_POST['role_id'];
        }

        $selected_user_id = isset($_GET['external_user_id']) ? (int) $_GET['external_user_id'] : 0;
        if ($selected_user_id === 0 && isset($_POST['external_user_id'])) {
            $selected_user_id = (int) $_POST['external_user_id'];
        }

        $selected_role_permissions = $selected_role_id > 0 ? self::get_role_permissions($db, $selected_role_id) : [];
        $selected_user_roles = $selected_user_id > 0 ? self::get_user_roles($db, $selected_user_id) : [];
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('VIS Rollenverwaltung', 'vis'); ?></h1>
            <p><?php esc_html_e('Verwalten Sie Rollen, Rollenberechtigungen und Benutzer-Rollen in der externen VIS-Datenbank.', 'vis'); ?></p>

            <?php if ($message !== '') : ?>
                <div class="notice notice-success"><p><?php echo esc_html($message); ?></p></div>
            <?php endif; ?>

            <h2><?php esc_html_e('Neue Rolle anlegen', 'vis'); ?></h2>
            <form method="post">
                <input type="hidden" name="vis_action" value="create_role" />
                <input type="hidden" name="vis_role_management_nonce" value="<?php echo esc_attr(wp_create_nonce('vis_role_management_action')); ?>" />
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="vis_role_key"><?php esc_html_e('Role Key', 'vis'); ?></label></th>
                        <td><input id="vis_role_key" name="role_key" required /></td>
                    </tr>
                    <tr>
                        <th><label for="vis_role_label"><?php esc_html_e('Bezeichnung', 'vis'); ?></label></th>
                        <td><input id="vis_role_label" name="label" required /></td>
                    </tr>
                </table>
                <?php submit_button(__('Rolle erstellen', 'vis'), 'secondary', 'submit', false); ?>
            </form>

            <hr />
            <h2><?php esc_html_e('Rollenberechtigungen', 'vis'); ?></h2>
            <form method="get" style="margin-bottom:1rem;">
                <input type="hidden" name="page" value="vis-role-management" />
                <label for="role_id"><strong><?php esc_html_e('Rolle auswählen', 'vis'); ?></strong></label>
                <select id="role_id" name="role_id">
                    <option value="0"><?php esc_html_e('Bitte auswählen', 'vis'); ?></option>
                    <?php foreach ($roles as $role) : ?>
                        <option value="<?php echo esc_attr((string) $role['id']); ?>" <?php selected($selected_role_id === (int) $role['id']); ?>>
                            <?php echo esc_html((string) $role['label'] . ' (' . (string) $role['role_key'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php submit_button(__('Laden', 'vis'), 'secondary', 'submit', false); ?>
            </form>

            <?php if ($selected_role_id > 0) : ?>
                <form method="post">
                    <input type="hidden" name="vis_action" value="save_role_permissions" />
                    <input type="hidden" name="role_id" value="<?php echo esc_attr((string) $selected_role_id); ?>" />
                    <input type="hidden" name="vis_role_management_nonce" value="<?php echo esc_attr(wp_create_nonce('vis_role_management_action')); ?>" />
                    <?php foreach ($permissions as $permission) : ?>
                        <label style="display:block; margin:0.25rem 0;">
                            <input type="checkbox" name="permissions[]" value="<?php echo esc_attr((string) $permission['permission_key']); ?>" <?php checked(in_array((string) $permission['permission_key'], $selected_role_permissions, true)); ?> />
                            <?php echo esc_html((string) $permission['label'] . ' (' . (string) $permission['permission_key'] . ')'); ?>
                        </label>
                    <?php endforeach; ?>
                    <?php submit_button(__('Rollenrechte speichern', 'vis')); ?>
                </form>
            <?php endif; ?>

            <hr />
            <h2><?php esc_html_e('Benutzer-Rollen', 'vis'); ?></h2>
            <form method="get" style="margin-bottom:1rem;">
                <input type="hidden" name="page" value="vis-role-management" />
                <label for="external_user_id"><strong><?php esc_html_e('Benutzer auswählen', 'vis'); ?></strong></label>
                <select id="external_user_id" name="external_user_id">
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
                    <input type="hidden" name="vis_action" value="save_user_roles" />
                    <input type="hidden" name="external_user_id" value="<?php echo esc_attr((string) $selected_user_id); ?>" />
                    <input type="hidden" name="vis_role_management_nonce" value="<?php echo esc_attr(wp_create_nonce('vis_role_management_action')); ?>" />
                    <?php foreach ($roles as $role) : ?>
                        <label style="display:block; margin:0.25rem 0;">
                            <input type="checkbox" name="roles[]" value="<?php echo esc_attr((string) $role['id']); ?>" <?php checked(in_array((int) $role['id'], $selected_user_roles, true)); ?> />
                            <?php echo esc_html((string) $role['label'] . ' (' . (string) $role['role_key'] . ')'); ?>
                        </label>
                    <?php endforeach; ?>
                    <?php submit_button(__('Benutzerrollen speichern', 'vis')); ?>
                </form>
            <?php endif; ?>

            <hr />
            <h2><?php esc_html_e('Bulk-Workflow: Rolle mehreren Benutzern zuweisen', 'vis'); ?></h2>
            <form method="post">
                <input type="hidden" name="vis_action" value="bulk_assign_role" />
                <input type="hidden" name="vis_role_management_nonce" value="<?php echo esc_attr(wp_create_nonce('vis_role_management_action')); ?>" />
                <p>
                    <label for="bulk_role_id"><strong><?php esc_html_e('Rolle', 'vis'); ?></strong></label><br />
                    <select id="bulk_role_id" name="bulk_role_id" required>
                        <option value=""><?php esc_html_e('Bitte auswählen', 'vis'); ?></option>
                        <?php foreach ($roles as $role) : ?>
                            <option value="<?php echo esc_attr((string) $role['id']); ?>"><?php echo esc_html((string) $role['label']); ?></option>
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
                <?php submit_button(__('Bulk-Zuweisung ausführen', 'vis'), 'secondary'); ?>
            </form>
        </div>
        <?php
    }

    private static function create_role(wpdb $db, string $role_key, string $label): int
    {
        $roles_table = VIS_External_DB::prefixed_table('roles');
        $db->insert(
            $roles_table,
            [
                'role_key' => $role_key,
                'label' => $label,
                'is_active' => 1,
            ],
            ['%s', '%s', '%d']
        );

        return (int) $db->insert_id;
    }

    private static function get_roles(wpdb $db): array
    {
        $roles_table = VIS_External_DB::prefixed_table('roles');
        $rows = $db->get_results("SELECT id, role_key, label FROM {$roles_table} WHERE is_active = 1 ORDER BY label ASC", ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    private static function get_permissions(wpdb $db): array
    {
        $permissions_table = VIS_External_DB::prefixed_table('permissions');
        $rows = $db->get_results("SELECT permission_key, label FROM {$permissions_table} WHERE is_active = 1 ORDER BY label ASC", ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    private static function get_users(wpdb $db): array
    {
        $users_table = VIS_External_DB::prefixed_table('users');
        $rows = $db->get_results("SELECT id, login, display_name FROM {$users_table} WHERE status = 'active' ORDER BY display_name ASC", ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    private static function get_role_permissions(wpdb $db, int $role_id): array
    {
        $role_permissions_table = VIS_External_DB::prefixed_table('role_permissions');
        $rows = $db->get_col($db->prepare("SELECT permission_key FROM {$role_permissions_table} WHERE role_id = %d AND is_granted = 1", $role_id));

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter(array_map('sanitize_key', $rows)));
    }

    private static function get_user_roles(wpdb $db, int $user_id): array
    {
        $user_roles_table = VIS_External_DB::prefixed_table('user_roles');
        $rows = $db->get_col($db->prepare("SELECT role_id FROM {$user_roles_table} WHERE user_id = %d AND is_active = 1", $user_id));

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_map('intval', $rows));
    }

    private static function save_role_permissions(wpdb $db, int $role_id, array $permissions): void
    {
        $role_permissions_table = VIS_External_DB::prefixed_table('role_permissions');
        $permissions_table = VIS_External_DB::prefixed_table('permissions');

        $allowed = $db->get_col("SELECT permission_key FROM {$permissions_table} WHERE is_active = 1");
        $allowed = is_array($allowed) ? array_map('sanitize_key', $allowed) : [];
        $permissions = array_values(array_intersect($allowed, $permissions));

        $db->query($db->prepare("DELETE FROM {$role_permissions_table} WHERE role_id = %d", $role_id));

        foreach ($permissions as $permission_key) {
            $db->insert(
                $role_permissions_table,
                [
                    'role_id' => $role_id,
                    'permission_key' => $permission_key,
                    'is_granted' => 1,
                ],
                ['%d', '%s', '%d']
            );
        }
    }

    private static function save_user_roles(wpdb $db, int $user_id, array $roles): void
    {
        $user_roles_table = VIS_External_DB::prefixed_table('user_roles');
        $roles_table = VIS_External_DB::prefixed_table('roles');

        $allowed = $db->get_col("SELECT id FROM {$roles_table} WHERE is_active = 1");
        $allowed = is_array($allowed) ? array_map('intval', $allowed) : [];
        $roles = array_values(array_intersect($allowed, $roles));

        $db->query($db->prepare("DELETE FROM {$user_roles_table} WHERE user_id = %d", $user_id));

        foreach ($roles as $role_id) {
            $db->insert(
                $user_roles_table,
                [
                    'user_id' => $user_id,
                    'role_id' => (int) $role_id,
                    'is_active' => 1,
                ],
                ['%d', '%d', '%d']
            );
        }
    }

    private static function bulk_assign_role(wpdb $db, int $role_id, array $user_ids): int
    {
        $user_roles_table = VIS_External_DB::prefixed_table('user_roles');
        $roles_table = VIS_External_DB::prefixed_table('roles');

        $is_valid_role = (int) $db->get_var($db->prepare("SELECT id FROM {$roles_table} WHERE id = %d AND is_active = 1", $role_id));
        if ($is_valid_role <= 0) {
            return 0;
        }

        $affected = 0;
        foreach ($user_ids as $user_id) {
            if ($user_id <= 0) {
                continue;
            }

            $db->query($db->prepare("DELETE FROM {$user_roles_table} WHERE user_id = %d AND role_id = %d", $user_id, $role_id));
            $inserted = $db->insert(
                $user_roles_table,
                [
                    'user_id' => $user_id,
                    'role_id' => $role_id,
                    'is_active' => 1,
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
