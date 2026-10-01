<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Messages\HuntCorrectionMessageService;
use ChassesAuTresor\Core\Progress\HuntStatusUpdater;

/**
 * Process the legacy page-template route used to request hunt validation.
 */
final class HuntValidationRequestRouteHandler
{
    public static function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_redirect(home_url());
            exit;
        }

        $userId = (int) get_current_user_id();
        $huntId = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;

        if ($userId <= 0 || $huntId <= 0 || get_post_type($huntId) !== 'chasse') {
            wp_redirect(home_url());
            exit;
        }

        if (
            !isset($_POST['validation_chasse_nonce'])
            || !wp_verify_nonce($_POST['validation_chasse_nonce'], 'validation_chasse_' . $huntId)
        ) {
            wp_die(__('Vérification de sécurité échouée.', 'chassesautresor-com'));
        }

        if (!(new HuntValidationAccessResolver())->canRequest($huntId, $userId)) {
            wp_die(__('Conditions non remplies.', 'chassesautresor-com'));
        }

        (new HuntStatusUpdater())->synchronizePublication($huntId, 'en_attente');
        update_field('chasse_cache_statut', 'en_attente', $huntId);
        (new HuntCorrectionMessageService())->clear($huntId);

        wp_redirect(add_query_arg('validation_demandee', '1', get_permalink($huntId)));
        exit;
    }
}
