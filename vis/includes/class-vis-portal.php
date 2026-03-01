<?php

if (! defined('ABSPATH')) {
    exit;
}

class VIS_Portal
{
    public static function render_portal_shortcode(): string
    {
        if (! is_user_logged_in()) {
            return '<p>' . esc_html__('Bitte melden Sie sich an, um das VIS-Portal zu nutzen.', 'vis') . '</p>'
                . wp_login_form(['echo' => false]);
        }

        $user = wp_get_current_user();
        if (! user_can($user, 'vis_access_portal')) {
            return '<p>' . esc_html__('Sie haben keine Berechtigung für das VIS-Portal.', 'vis') . '</p>';
        }

        $enabled_modules = VIS_Modules::get_enabled_modules_for_user((int) $user->ID);
        $available = VIS_Modules::get_available_modules();

        ob_start();
        ?>
        <div class="vis-portal">
            <h2><?php esc_html_e('VIS Portal', 'vis'); ?></h2>
            <p><?php echo esc_html(sprintf(__('Willkommen, %s.', 'vis'), $user->display_name)); ?></p>

            <?php if ($enabled_modules === []) : ?>
                <p><?php esc_html_e('Aktuell sind für Sie keine Module freigeschaltet.', 'vis'); ?></p>
            <?php else : ?>
                <ul>
                    <?php foreach ($enabled_modules as $module_key) : ?>
                        <?php if (! isset($available[$module_key])) { continue; } ?>
                        <li>
                            <strong><?php echo esc_html($available[$module_key]['label']); ?></strong><br />
                            <span><?php echo esc_html($available[$module_key]['description']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}
