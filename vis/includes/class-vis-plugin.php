<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vis-external-db.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vis-settings.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vis-auth.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vis-access.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vis-schema-manager.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vis-modules.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vis-bildungsportal.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vis-portal.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vis-user-management.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vis-role-management.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vis-audit-log.php';

class VIS_Plugin
{
    public function run(): void
    {
        add_action('init', [$this, 'load_textdomain']);
        add_action('init', ['VIS_Auth', 'bootstrap_session'], 1);

        add_shortcode('vis_portal', ['VIS_Portal', 'render_portal_shortcode']);

        if (is_admin()) {
            add_action('admin_menu', ['VIS_Settings', 'register_admin_page']);
            add_action('admin_menu', ['VIS_User_Management', 'register_admin_page']);
            add_action('admin_menu', ['VIS_Role_Management', 'register_admin_page']);
            add_action('admin_menu', ['VIS_Audit_Log', 'register_admin_page']);
            add_action('admin_init', ['VIS_Settings', 'register_settings']);
            add_action('admin_init', ['VIS_Schema_Manager', 'maybe_auto_migrate'], 20);
        }
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain('vis', false, dirname(plugin_basename(__DIR__)) . '/languages');
    }
}
