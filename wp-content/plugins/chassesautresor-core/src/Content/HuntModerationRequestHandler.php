<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Support\CoreServiceFactory;
use ChassesAuTresor\Core\Progress\HuntStatusUpdater;
use ChassesAuTresor\Core\Progress\RiddleSystemStateUpdater;
use WP_User;

final class HuntModerationRequestHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('admin_post_traiter_validation_chasse', [self::class, 'handle']);
    }

    public static function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['validation_admin_action'])) {
            return;
        }

        $huntId = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
        $action = sanitize_text_field(wp_unslash($_POST['validation_admin_action']));
        $moderation = new HuntModerationService();
        $error = $moderation->requestError(
            current_user_can('administrator'),
            $huntId,
            (string) get_post_type($huntId),
            $action
        );

        if ($error === 'access') {
            wp_die(__('Accès refusé.', 'chassesautresor-com'));
        }
        if ($error !== null) {
            wp_die(__('Requête de modération invalide.', 'chassesautresor-com'));
        }

        if (
            !isset($_POST['validation_admin_nonce'])
            || !wp_verify_nonce($_POST['validation_admin_nonce'], 'validation_admin_' . $huntId)
        ) {
            wp_die(__('Nonce invalide.', 'chassesautresor-com'));
        }

        $riddleIds = array_map('intval', (array) recuperer_enigmes_associees($huntId));
        $organizerId = (int) get_organisateur_from_chasse($huntId);
        $users = $organizerId > 0 ? (array) get_field('utilisateurs_associes', $organizerId) : [];
        $userIds = array_values(array_filter(array_map([self::class, 'normalizeUserId'], $users)));
        $plan = $moderation->plan($action);

        if ($action !== 'supprimer') {
            (new HuntModerationMutationService())->apply(
                $huntId,
                $riddleIds,
                (array) (get_field('champs_caches', $huntId) ?: []),
                $plan,
                'wp_update_post',
                'update_field',
                static fn (int $id): string => (new HuntStatusUpdater())->refresh($id),
                static fn (int $id): string => (new RiddleSystemStateUpdater())->refresh($id)
            );
        }

        if ($action === 'valider') {
            self::promoteOrganizer($organizerId, $userIds);
        } elseif ($action === 'supprimer' && !chasse_trash_with_children($huntId)) {
            wp_die(__('Impossible de supprimer la chasse.', 'chassesautresor-com'));
        }

        global $wpdb;
        $message = isset($_POST['validation_admin_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['validation_admin_message']))
            : '';
        (new HuntModerationNotificationService(CoreServiceFactory::accountMessages($wpdb)))->notify(
            $action,
            $organizerId,
            $huntId,
            $userIds,
            (string) get_the_title($huntId),
            (string) get_permalink($huntId),
            $message
        );

        wp_safe_redirect(home_url('/mon-compte/organisateurs/'));
        exit;
    }

    /** @param mixed $user */
    private static function normalizeUserId($user): int
    {
        return is_object($user) && isset($user->ID) ? (int) $user->ID : (int) $user;
    }

    /** @param int[] $userIds */
    private static function promoteOrganizer(int $organizerId, array $userIds): void
    {
        (new HuntModerationOrganizerService())->promote(
            $organizerId,
            $userIds,
            'get_post_status',
            static function (int $id): void {
                wp_update_post(['ID' => $id, 'post_status' => 'publish']);
            },
            static function (int $userId): void {
                $user = new WP_User($userId);
                $user->add_role(ROLE_ORGANISATEUR);
                $user->remove_role(ROLE_ORGANISATEUR_CREATION);
            }
        );
    }
}
