<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vis-roles.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vis-modules.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vis-portal.php';

class VIS_Plugin
{
    public function run(): void
    {
        add_action('init', [$this, 'load_textdomain']);
        add_action('init', ['VIS_Roles', 'register_role_and_caps']);

        add_shortcode('vis_portal', ['VIS_Portal', 'render_portal_shortcode']);

        if (is_admin()) {
            add_action('admin_menu', ['VIS_Modules', 'register_admin_page']);
            add_action('admin_init', ['VIS_Modules', 'register_settings']);
        }
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain('vis', false, dirname(plugin_basename(__DIR__)) . '/languages');
    }
}
