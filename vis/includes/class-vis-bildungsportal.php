<?php

if (! defined('ABSPATH')) {
    exit;
}

class VIS_Bildungsportal
{
    public static function render(array $external_user): string
    {
        $db = VIS_External_DB::create_connection();
        if (! $db instanceof wpdb) {
            return '<p>' . esc_html__('Keine Verbindung zur externen Datenbank.', 'vis') . '</p>';
        }

        $message = '';
        if (
            isset($_POST['vis_action'])
            && sanitize_key((string) $_POST['vis_action']) === 'bildung_register'
            && isset($_POST['vis_nonce'])
            && wp_verify_nonce(sanitize_text_field((string) $_POST['vis_nonce']), 'vis_bildung_register')
        ) {
            $offer_id = isset($_POST['offer_id']) ? (int) $_POST['offer_id'] : 0;
            if ($offer_id > 0) {
                $ok = self::register_user_for_offer($db, (int) $external_user['id'], $offer_id);
                if ($ok) {
                    VIS_Audit_Log::write('bildungsportal_register_offer', 'education_offer', $offer_id, [
                        'external_user_id' => (int) $external_user['id'],
                    ]);
                    $message = __('Anmeldung wurde gespeichert.', 'vis');
                } else {
                    $message = __('Anmeldung konnte nicht gespeichert werden.', 'vis');
                }
            }
        }

        $offers = self::get_open_offers($db);
        $my_registrations = self::get_registered_offer_ids($db, (int) $external_user['id']);

        ob_start();
        ?>
        <div class="vis-bildungsportal">
            <h3><?php esc_html_e('Bildungsportal', 'vis'); ?></h3>
            <p><?php esc_html_e('Hier können Sie verfügbare Bildungsangebote einsehen und anmelden.', 'vis'); ?></p>

            <?php if ($message !== '') : ?>
                <div class="notice notice-success"><p><?php echo esc_html($message); ?></p></div>
            <?php endif; ?>

            <?php if ($offers === []) : ?>
                <p><?php esc_html_e('Aktuell sind keine Bildungsangebote verfügbar.', 'vis'); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('Titel', 'vis'); ?></th>
                        <th><?php esc_html_e('Datum', 'vis'); ?></th>
                        <th><?php esc_html_e('Ort', 'vis'); ?></th>
                        <th><?php esc_html_e('Aktion', 'vis'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($offers as $offer) : ?>
                        <?php $offer_id = (int) $offer['id']; ?>
                        <tr>
                            <td><?php echo esc_html((string) $offer['title']); ?></td>
                            <td><?php echo esc_html((string) $offer['offer_date']); ?></td>
                            <td><?php echo esc_html((string) $offer['location']); ?></td>
                            <td>
                                <?php if (in_array($offer_id, $my_registrations, true)) : ?>
                                    <?php esc_html_e('Bereits angemeldet', 'vis'); ?>
                                <?php else : ?>
                                    <form method="post" style="margin:0;">
                                        <input type="hidden" name="vis_action" value="bildung_register" />
                                        <input type="hidden" name="offer_id" value="<?php echo esc_attr((string) $offer_id); ?>" />
                                        <input type="hidden" name="vis_nonce" value="<?php echo esc_attr(wp_create_nonce('vis_bildung_register')); ?>" />
                                        <button type="submit" class="button button-secondary"><?php esc_html_e('Anmelden', 'vis'); ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function get_open_offers(wpdb $db): array
    {
        $offers_table = VIS_External_DB::prefixed_table('education_offers');
        $rows = $db->get_results("SELECT id, title, offer_date, location FROM {$offers_table} WHERE is_active = 1 ORDER BY offer_date ASC", ARRAY_A);

        return is_array($rows) ? $rows : [];
    }

    private static function get_registered_offer_ids(wpdb $db, int $external_user_id): array
    {
        $registrations_table = VIS_External_DB::prefixed_table('education_registrations');
        $rows = $db->get_col($db->prepare("SELECT offer_id FROM {$registrations_table} WHERE external_user_id = %d", $external_user_id));

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_map('intval', $rows));
    }

    private static function register_user_for_offer(wpdb $db, int $external_user_id, int $offer_id): bool
    {
        $registrations_table = VIS_External_DB::prefixed_table('education_registrations');

        $db->query($db->prepare(
            "DELETE FROM {$registrations_table} WHERE external_user_id = %d AND offer_id = %d",
            $external_user_id,
            $offer_id
        ));

        $result = $db->insert(
            $registrations_table,
            [
                'external_user_id' => $external_user_id,
                'offer_id' => $offer_id,
                'created_at' => current_time('mysql', true),
            ],
            ['%d', '%d', '%s']
        );

        return $result !== false;
    }
}
