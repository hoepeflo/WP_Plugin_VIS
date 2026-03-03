<?php

if (! defined('ABSPATH')) {
    exit;
}

class VIS_Portal
{
    public static function render_portal_shortcode(): string
    {
        $message = '';

        if (isset($_POST['vis_action']) && $_POST['vis_action'] === 'logout') {
            if (isset($_POST['vis_nonce']) && wp_verify_nonce(sanitize_text_field((string) $_POST['vis_nonce']), 'vis_portal_action')) {
                VIS_Auth::logout();
            }
        }

        if (isset($_POST['vis_action']) && $_POST['vis_action'] === 'login') {
            $login = isset($_POST['vis_login']) ? sanitize_user((string) $_POST['vis_login']) : '';
            $password = isset($_POST['vis_password']) ? (string) $_POST['vis_password'] : '';
            $nonce_ok = isset($_POST['vis_nonce']) && wp_verify_nonce(sanitize_text_field((string) $_POST['vis_nonce']), 'vis_portal_action');

            if (! $nonce_ok || $login === '' || $password === '') {
                $message = esc_html__('Ungültige Login-Daten.', 'vis');
            } elseif (! VIS_Auth::login($login, $password)) {
                $message = esc_html__('Anmeldung fehlgeschlagen. Bitte Zugangsdaten prüfen.', 'vis');
            }
        }

        if (! VIS_External_DB::is_configured()) {
            return '<p>' . esc_html__('VIS ist noch nicht konfiguriert. Bitte externe Datenbank in den VIS-Einstellungen hinterlegen.', 'vis') . '</p>';
        }

        $user = VIS_Auth::current_user();
        if (! is_array($user)) {
            return self::render_login_form($message);
        }

        $modules = VIS_Modules::get_enabled_modules_for_external_user((int) $user['id']);
        $active_module = isset($_GET['vis_module']) ? sanitize_key((string) $_GET['vis_module']) : '';

        ob_start();
        ?>
        <div class="vis-portal">
            <h2><?php esc_html_e('VIS Portal', 'vis'); ?></h2>
            <p><?php echo esc_html(sprintf(__('Willkommen, %s.', 'vis'), $user['display_name'])); ?></p>
            <?php if ($user['club_name'] !== '') : ?>
                <p><?php echo esc_html(sprintf(__('Verein: %s', 'vis'), $user['club_name'])); ?></p>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="vis_action" value="logout" />
                <input type="hidden" name="vis_nonce" value="<?php echo esc_attr(wp_create_nonce('vis_portal_action')); ?>" />
                <button type="submit"><?php esc_html_e('Abmelden', 'vis'); ?></button>
            </form>

            <h3><?php esc_html_e('Ihre freigeschalteten Module', 'vis'); ?></h3>
            <?php if ($modules === []) : ?>
                <p><?php esc_html_e('Aktuell sind keine Module für Ihren Account freigeschaltet.', 'vis'); ?></p>
            <?php else : ?>
                <ul>
                    <?php foreach ($modules as $module) : ?>
                        <li>
                            <strong>
                                <a href="<?php echo esc_url((string) ($module['module_url'] ?? '#')); ?>">
                                    <?php echo esc_html((string) ($module['label'] ?? '')); ?>
                                </a>
                            </strong><br />
                            <span><?php echo esc_html((string) ($module['description'] ?? '')); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php echo self::render_module_content($active_module, $modules, $user); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function render_module_content(string $active_module, array $modules, array $external_user): string
    {
        if ($active_module === '') {
            return '';
        }

        foreach ($modules as $module) {
            if (sanitize_key((string) ($module['module_key'] ?? '')) !== $active_module) {
                continue;
            }

            return VIS_Modules::render_module($active_module, $external_user);
        }

        return '<p>' . esc_html__('Unbekanntes oder nicht freigegebenes Modul.', 'vis') . '</p>';
    }

    private static function render_login_form(string $message = ''): string
    {
        ob_start();
        ?>
        <div class="vis-portal-login">
            <h2><?php esc_html_e('VIS Login', 'vis'); ?></h2>
            <?php if ($message !== '') : ?>
                <p><?php echo esc_html($message); ?></p>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="vis_action" value="login" />
                <input type="hidden" name="vis_nonce" value="<?php echo esc_attr(wp_create_nonce('vis_portal_action')); ?>" />
                <p>
                    <label for="vis_login"><?php esc_html_e('Benutzername', 'vis'); ?></label><br />
                    <input id="vis_login" type="text" name="vis_login" required />
                </p>
                <p>
                    <label for="vis_password"><?php esc_html_e('Passwort', 'vis'); ?></label><br />
                    <input id="vis_password" type="password" name="vis_password" required />
                </p>
                <p>
                    <button type="submit"><?php esc_html_e('Anmelden', 'vis'); ?></button>
                </p>
            </form>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}
