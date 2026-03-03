<?php

if (! defined('ABSPATH')) {
    exit;
}

class VIS_Roles
{
    public static function register_role_and_caps(): void
    {
        $caps = [
            'read' => true,
            'vis_access_portal' => true,
            'vis_manage_own_modules' => true,
        ];

        add_role(
            'vis_vereinsvertreter',
            __('VIS Vereinsvertreter', 'vis'),
            $caps
        );

        $admin = get_role('administrator');
        if ($admin instanceof WP_Role) {
            $admin->add_cap('vis_manage_modules');
            $admin->add_cap('vis_access_portal');
        }
    }
}
