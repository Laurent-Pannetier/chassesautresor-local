<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

final class HuntEngagementRouteHandler
{
    public static function handle(): void
    {
        $userId = (int) get_current_user_id();
        $huntId = isset($_POST['chasse_id']) ? (int) wp_unslash($_POST['chasse_id']) : 0;
        if ($userId <= 0 || $huntId <= 0) {
            self::redirectHome();
        }

        $nonce = isset($_POST['engager_chasse_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['engager_chasse_nonce']))
            : '';
        $cost = (int) get_field('chasse_infos_cout_points', $huntId);
        global $wpdb;
        $points = CoreServiceFactory::points($wpdb);
        $engagements = CoreServiceFactory::huntEngagement($wpdb);
        $result = (new HuntEngagementApplicationService())->engage(
            $userId,
            $huntId,
            (string) get_post_type($huntId),
            $nonce !== '' && wp_verify_nonce($nonce, 'engager_chasse_' . $huntId) !== false,
            current_user_can('administrator'),
            self::isAssociatedOrganizer($userId, $huntId),
            $cost,
            $points->getBalance($userId),
            static function (int $targetUserId, int $targetHuntId) use ($engagements): bool {
                $inserted = $engagements->engage($targetUserId, $targetHuntId, current_time('mysql', true));
                if ($inserted) {
                    do_action('chasse_engagement_created', $targetHuntId);
                }
                return $inserted;
            },
            static function (int $targetUserId, int $targetHuntId, int $amount) use ($points): void {
                $reason = sprintf(__('Déblocage de la chasse #%d', 'chassesautresor-com'), $targetHuntId);
                $points->deduct($targetUserId, $amount, $reason, 'chasse', $targetHuntId);
            }
        );

        if ($result['status'] === 'invalid_nonce') {
            wp_die(__('Échec de vérification de sécurité', 'chassesautresor-com'));
        }

        $url = get_permalink($huntId);
        if ($result['status'] === 'points_insuffisants') {
            $url = add_query_arg('erreur', 'points_insuffisants', $url);
        } elseif ($result['status'] !== 'success') {
            $url = add_query_arg('erreur', 'engagement', $url);
        }
        wp_safe_redirect($url);
        exit;
    }

    private static function isAssociatedOrganizer(int $userId, int $huntId): bool
    {
        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(get_field('organisateur_id', $huntId));
        if ($organizerId === null) {
            return false;
        }

        return in_array(
            $userId,
            $relationships->normalizeIds((array) get_field('utilisateurs_associes', $organizerId)),
            true
        );
    }

    private static function redirectHome(): void
    {
        wp_safe_redirect(home_url('/'));
        exit;
    }
}
