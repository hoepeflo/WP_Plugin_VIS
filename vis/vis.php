<?php
/**
 * Plugin Name: VIS - Verbandsinformationssystem
 * Description: Modulares Verbandsinformationssystem für den Kreisschützenverband Fallingbostel.
 * Version: 0.1.0
 * Author: VIS Team
 * Requires at least: 6.2
 * Requires PHP: 8.0
 * Text Domain: vis
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-vis-plugin.php';

function vis_run_plugin(): void
{
    $plugin = new VIS_Plugin();
    $plugin->run();
}
vis_run_plugin();
