<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Site\SiteExperienceService;

/** AJAX transport for the profile Edit / Activate switch. */
final class HuntLifecycleAjaxHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('wp_ajax_cta_toggle_hunt_lifecycle', [self::class, 'handle']);
    }

    public static function handle(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
        }

        $huntId = isset($_POST['hunt_id']) ? (int) $_POST['hunt_id'] : 0;
        $activate = isset($_POST['activate']) && (string) $_POST['activate'] === '1';
        $nonce = (string) ($_POST['nonce'] ?? '');

        if ($huntId <= 0 || !wp_verify_nonce($nonce, 'cta_toggle_hunt_lifecycle_' . $huntId)) {
            wp_send_json_error(['message' => __('Requête invalide.', 'chassesautresor-com')], 400);
        }

        $isDemo = (new SiteExperienceService())->isDemoMode(cat_get_site_experience_settings());
        $result = (new HuntLifecycleApplicationService())->toggle(
            $huntId,
            (int) get_current_user_id(),
            $activate,
            $isDemo
        );

        if (!$result['ok']) {
            $messages = [
                'forbidden' => __('Action non autorisée.', 'chassesautresor-com'),
                'hunt' => __('Chasse introuvable.', 'chassesautresor-com'),
                'action' => __('Action impossible dans cet état.', 'chassesautresor-com'),
            ];
            wp_send_json_error([
                'message' => $messages[$result['error']] ?? __('Erreur', 'chassesautresor-com'),
                'view' => $result['view'],
            ], 403);
        }

        wp_send_json_success(['view' => $result['view']]);
    }
}
