<?php

if (! defined('ABSPATH')) {
    exit;
}

class VIS_Modules
{
    public const OPTION_ENABLED_MODULES = 'vis_enabled_modules_global';

    public static function get_available_modules(): array
    {
        return [
            'bildungsportal' => [
                'label' => __('Bildungsportal', 'vis'),
                'description' => __('Anmeldung zu Bildungsangeboten des KSV Fallingbostel.', 'vis'),
            ],
            'kidscup' => [
                'label' => __('KidsCup', 'vis'),
                'description' => __('Verwaltung und Durchführung des KidsCup-Wettbewerbs.', 'vis'),
            ],
        ];
    }

    public static function get_globally_enabled_modules(): array
    {
        $enabled = get_option(self::OPTION_ENABLED_MODULES, []);
        return is_array($enabled) ? array_values(array_filter($enabled, 'is_string')) : [];
    }

    public static function get_enabled_modules_for_user(int $user_id): array
    {
        $global = self::get_globally_enabled_modules();
        $user_modules = get_user_meta($user_id, 'vis_user_modules', true);

        if (! is_array($user_modules) || $user_modules === []) {
            return $global;
        }

        return array_values(array_intersect($global, $user_modules));
    }

    public static function register_settings(): void
    {
        register_setting('vis_modules', self::OPTION_ENABLED_MODULES, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize_modules'],
            'default' => [],
        ]);
    }

    public static function sanitize_modules($modules): array
    {
        if (! is_array($modules)) {
            return [];
        }

        $allowed_keys = array_keys(self::get_available_modules());
        $sanitized = array_map('sanitize_key', $modules);

        return array_values(array_intersect($allowed_keys, $sanitized));
    }

    public static function register_admin_page(): void
    {
        add_options_page(
            __('VIS Module', 'vis'),
            __('VIS Module', 'vis'),
            'manage_options',
            'vis-modules',
            [self::class, 'render_admin_page']
        );
    }

    public static function render_admin_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $available = self::get_available_modules();
        $enabled = self::get_globally_enabled_modules();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('VIS Module verwalten', 'vis'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('vis_modules'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                    <?php foreach ($available as $module_key => $module_data) : ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($module_data['label']); ?></th>
                            <td>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="<?php echo esc_attr(self::OPTION_ENABLED_MODULES); ?>[]"
                                        value="<?php echo esc_attr($module_key); ?>"
                                        <?php checked(in_array($module_key, $enabled, true)); ?>
                                    />
                                    <?php echo esc_html($module_data['description']); ?>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php submit_button(__('Module speichern', 'vis')); ?>
            </form>
        </div>
        <?php
    }
}
