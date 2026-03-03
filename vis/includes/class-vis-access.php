<?php

if (! defined('ABSPATH')) {
    exit;
}

class VIS_Access
{
    public static function get_user_permissions(int $user_id): array
    {
        $db = VIS_External_DB::create_connection();
        if (! $db instanceof wpdb) {
            return [];
        }

        $user_permissions_table = VIS_External_DB::prefixed_table('user_permissions');
        $role_permissions_table = VIS_External_DB::prefixed_table('role_permissions');
        $user_roles_table = VIS_External_DB::prefixed_table('user_roles');

        $direct_permissions = $db->get_col(
            $db->prepare(
                "SELECT permission_key
                 FROM {$user_permissions_table}
                 WHERE user_id = %d AND is_granted = 1",
                $user_id
            )
        );

        $inherited_permissions = $db->get_col(
            $db->prepare(
                "SELECT rp.permission_key
                 FROM {$role_permissions_table} rp
                 INNER JOIN {$user_roles_table} ur ON ur.role_id = rp.role_id
                 WHERE ur.user_id = %d AND ur.is_active = 1 AND rp.is_granted = 1",
                $user_id
            )
        );

        $merged = array_merge(
            is_array($direct_permissions) ? $direct_permissions : [],
            is_array($inherited_permissions) ? $inherited_permissions : []
        );

        $sanitized = array_map(static function ($permission): string {
            return sanitize_key((string) $permission);
        }, $merged);

        return array_values(array_unique(array_filter($sanitized)));
    }

    public static function can_access_module(int $user_id, array $module): bool
    {
        if (! isset($module['required_permission']) || (string) $module['required_permission'] === '') {
            return true;
        }

        $required_permission = sanitize_key((string) $module['required_permission']);
        $permissions = self::get_user_permissions($user_id);

        return in_array($required_permission, $permissions, true);
    }
}
