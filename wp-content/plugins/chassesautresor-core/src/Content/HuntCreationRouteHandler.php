<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\OrganizerHuntQueryService;
use ChassesAuTresor\Core\Relationships\OrganizerRepository;

/**
 * WordPress route adapter for public hunt creation.
 */
class HuntCreationRouteHandler {
    public static function register(callable $addAction): void {
        $addAction('init', [self::class, 'registerRoute'], 10, 1);
        $addAction('template_redirect', [self::class, 'handle'], 10, 1);
    }

    public static function registerRoute(): void {
        add_rewrite_rule('^creer-chasse/?$', 'index.php?creer_chasse=1', 'top');
        add_rewrite_tag('%creer_chasse%', '1');
    }

    public static function handle(): void {
        if (get_query_var('creer_chasse') !== '1') {
            return;
        }

        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url());
            exit;
        }

        global $wpdb;
        $user = wp_get_current_user();
        $userId = (int) $user->ID;
        $roles = (array) $user->roles;
        $organizerId = (new OrganizerRepository($wpdb))->findIdForUser($userId) ?? 0;
        $queries = new OrganizerHuntQueryService();
        $hasHunts = $organizerId > 0
            && (new \WP_Query($queries->getExistingHuntQueryArgs($organizerId)))->have_posts();
        $hasPendingHunt = $organizerId > 0
            && (new \WP_Query($queries->getExistingHuntQueryArgs($organizerId, true)))->have_posts();
        $error = (new HuntCreationRequestService())->getError(
            $organizerId,
            current_user_can('administrator'),
            in_array('organisateur', $roles, true),
            in_array('organisateur_creation', $roles, true),
            $hasHunts,
            current_user_can('manage_options'),
            $organizerId > 0 && get_post_status($organizerId) === 'publish',
            $hasPendingHunt
        );
        if ($error !== null) {
            $messages = [
                'missing_organizer' => __('Aucun organisateur associé.', 'chassesautresor-com'),
                'hunt_limit_reached' => __('Limite atteinte', 'chassesautresor-com'),
                'access_denied' => __('Accès refusé', 'chassesautresor-com'),
                'pending_hunt_exists' => __(
                    'Une chasse est déjà en attente de validation.',
                    'chassesautresor-com'
                ),
            ];
            wp_die($messages[$error]);
        }

        $huntId = (new HuntPostFactory())->create(
            $userId,
            $organizerId,
            __('Nouvelle chasse', 'chassesautresor-com'),
            3902,
            current_time('Y-m-d H:i:s'),
            date('Y-m-d', strtotime('+2 years'))
        );
        if (is_wp_error($huntId)) {
            wp_die(__('Erreur lors de la création de la chasse.', 'chassesautresor-com'));
        }

        wp_redirect(get_preview_post_link($huntId));
        exit;
    }
}
