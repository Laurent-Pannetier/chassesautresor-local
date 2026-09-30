<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * WordPress route adapter for riddle creation.
 */
class RiddleCreationRouteHandler {
    public static function register(callable $addAction): void {
        $addAction('init', [RiddleRouteRegistrar::class, 'register'], 10, 1);
        $addAction('init', [RiddleRouteRegistrar::class, 'maybeFlush'], 20, 1);
        $addAction('template_redirect', [self::class, 'handle'], 10, 1);
    }

    /**
     * @return int|\WP_Error
     */
    public static function create(
        int $huntId,
        ?int $userId = null,
        ?callable $findOrganizerId = null
    ) {
        $userId = $userId ?? get_current_user_id();
        $hasValidHunt = get_post_type($huntId) === 'chasse';
        $hasValidUser = $hasValidHunt && $userId > 0 && get_userdata($userId);
        $findOrganizerId = $findOrganizerId ?? static function (int $postId): ?int {
            return (new RelationshipService())->normalizeId(
                get_field('chasse_cache_organisateur', $postId)
            );
        };
        $organizerId = $hasValidUser ? (int) $findOrganizerId($huntId) : 0;
        $creationError = (new RiddleCreationService())->getCreationError(
            $hasValidHunt,
            (bool) $hasValidUser,
            $organizerId > 0
        );

        if ($creationError !== null) {
            $errors = [
                'invalid_hunt' => ['chasse_invalide', __('ID de chasse invalide.', 'chassesautresor-com')],
                'invalid_user' => ['utilisateur_invalide', __('Utilisateur non connecté.', 'chassesautresor-com')],
                'missing_organizer' => [
                    'organisateur_introuvable',
                    __('Organisateur non lié à cette chasse.', 'chassesautresor-com'),
                ],
            ];

            return new \WP_Error($errors[$creationError][0], $errors[$creationError][1]);
        }

        $riddleId = (new RiddlePostFactory())->create(
            $huntId,
            $organizerId,
            $userId,
            defined('TITRE_DEFAUT_ENIGME') ? TITRE_DEFAUT_ENIGME : __('Énigme', 'chassesautresor-com'),
            (new \DateTime('+1 month'))->format('Y-m-d H:i:s')
        );
        if (!is_wp_error($riddleId)) {
            do_action('chassesautresor_riddle_created', $riddleId);
        }

        return $riddleId;
    }

    public static function handle(): void {
        if (get_query_var('creer_enigme') !== '1') {
            return;
        }

        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        do_action('litespeed_control_set_nocache');
        nocache_headers();

        $huntId = isset($_GET['chasse_id']) ? absint($_GET['chasse_id']) : 0;
        $hasValidNonce = (bool) wp_verify_nonce(
            sanitize_text_field(wp_unslash($_GET['nonce'] ?? '')),
            'creer_enigme'
        );
        $isLoggedIn = $hasValidNonce && is_user_logged_in();
        $hasValidHunt = $isLoggedIn && $huntId > 0 && get_post_type($huntId) === 'chasse';
        $requestError = (new RiddleCreationRequestService())->getRequestError(
            $hasValidNonce,
            $isLoggedIn,
            $hasValidHunt
        );

        if ($requestError === 'invalid_nonce') {
            wp_die(
                __('Action non autorisée.', 'chassesautresor-com'),
                __('Erreur', 'chassesautresor-com'),
                ['response' => 403]
            );
        }
        if ($requestError === 'authentication_required') {
            wp_redirect(wp_login_url());
            exit;
        }
        if ($requestError === 'invalid_hunt') {
            wp_die(
                __('Chasse non spécifiée ou invalide.', 'chassesautresor-com'),
                __('Erreur', 'chassesautresor-com'),
                ['response' => 400]
            );
        }

        $riddleId = self::create($huntId, get_current_user_id());
        if (is_wp_error($riddleId)) {
            wp_die(
                $riddleId->get_error_message(),
                __('Erreur', 'chassesautresor-com'),
                ['response' => 500]
            );
        }

        wp_redirect(add_query_arg('edition', 'open', get_preview_post_link($riddleId)));
        exit;
    }
}
