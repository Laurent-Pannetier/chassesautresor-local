<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

final class ConversionRequestHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('init', [self::class, 'handle']);
    }

    public static function handle(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !isset($_POST['demander_paiement'])) {
            return;
        }

        $nonce = isset($_POST['demande_paiement_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['demande_paiement_nonce']))
            : '';
        if (!wp_verify_nonce($nonce, 'demande_paiement_action')) {
            wp_die(__('❌ Vérification du nonce échouée.', 'chassesautresor-com'));
        }
        if (!is_user_logged_in()) {
            wp_die(__('❌ Vous devez être connecté pour effectuer cette action.', 'chassesautresor-com'));
        }

        global $wpdb;
        $userId = get_current_user_id();
        $points = isset($_POST['points_a_convertir']) ? (int) wp_unslash($_POST['points_a_convertir']) : 0;
        $minimum = (int) apply_filters('points_conversion_min', 500);
        $balance = CoreServiceFactory::points($wpdb)->getBalance($userId);
        $rate = (new ConversionSettingsService())->getRate();
        $result = (new ConversionRequestService(CoreServiceFactory::conversion($wpdb)))->request(
            $userId,
            $points,
            $minimum,
            $balance,
            $rate
        );

        if ($result['error'] === 'minimum') {
            wp_die(sprintf(
                /* translators: %d: minimum number of points. */
                __('❌ Le minimum pour une conversion est de %d points.', 'chassesautresor-com'),
                $minimum
            ));
        }
        if ($result['error'] === 'balance') {
            wp_die(__('❌ Vous n’avez pas assez de points pour effectuer cette conversion.', 'chassesautresor-com'));
        }
        if ($result['error'] !== '') {
            wp_die(__('❌ La demande de paiement n’a pas pu être enregistrée.', 'chassesautresor-com'));
        }

        self::notifyAdministrator($userId, $points, $result['amount']);
        wp_safe_redirect(add_query_arg('paiement_envoye', '1', home_url('/mon-compte/')));
        exit;
    }

    private static function notifyAdministrator(int $userId, int $points, float $amount): void
    {
        $subject = __('Nouvelle demande de paiement', 'chassesautresor-com');
        /* translators: 1: organizer ID, 2: euro amount, 3: number of points, 4: request date. */
        $format = __(
            "Organisateur ID : %1\$d\nMontant : %2\$.2f €\nPoints utilisés : %3\$d\nDate : %4\$s",
            'chassesautresor-com'
        );
        $message = sprintf(
            $format,
            $userId,
            $amount,
            $points,
            current_time('mysql')
        );
        wp_mail(get_option('admin_email'), $subject, $message);
    }
}
