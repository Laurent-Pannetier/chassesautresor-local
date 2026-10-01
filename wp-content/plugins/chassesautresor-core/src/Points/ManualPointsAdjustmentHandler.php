<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

final class ManualPointsAdjustmentHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('init', [self::class, 'handle']);
    }

    public static function handle(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !isset($_POST['modifier_points'])) {
            return;
        }

        $nonce = isset($_POST['gestion_points_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['gestion_points_nonce']))
            : '';
        if (!wp_verify_nonce($nonce, 'gestion_points_action')) {
            wp_die(__('❌ Vérification du nonce échouée.', 'chassesautresor-com'));
        }
        if (!current_user_can('administrator')) {
            wp_die(__('❌ Accès refusé.', 'chassesautresor-com'));
        }

        $userId = isset($_POST['utilisateur']) ? (int) wp_unslash($_POST['utilisateur']) : 0;
        $action = isset($_POST['type_modification'])
            ? sanitize_text_field(wp_unslash($_POST['type_modification']))
            : '';
        $amount = isset($_POST['nombre_points']) ? (int) wp_unslash($_POST['nombre_points']) : 0;
        if ($userId <= 0 || !get_userdata($userId)) {
            wp_die(__('❌ Utilisateur introuvable.', 'chassesautresor-com'));
        }

        global $wpdb;
        $points = CoreServiceFactory::points($wpdb);
        $adjustment = (new ManualPointsAdjustmentService())->prepare(
            $action,
            $amount,
            $points->getBalance($userId)
        );
        if ($adjustment['error'] === 'balance') {
            wp_die(__('❌ Impossible de retirer plus de points que l’utilisateur en possède.', 'chassesautresor-com'));
        }
        if ($adjustment['error'] !== '') {
            wp_die(__('❌ Données invalides.', 'chassesautresor-com'));
        }

        $points->changeBalance($userId, $adjustment['delta'], $adjustment['reason'], 'admin');
        $redirect = add_query_arg(
            ['section' => 'outils', 'points_modifies' => '1'],
            home_url('/mon-compte/')
        );
        wp_safe_redirect($redirect);
        exit;
    }
}
