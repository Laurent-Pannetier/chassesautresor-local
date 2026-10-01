<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Media\RiddleUploadDirectoryService;
use ChassesAuTresor\Core\Messages\AccountMessageService;
use ChassesAuTresor\Core\Messages\UserMessageRepository;
use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;
use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * WordPress AJAX adapter for trashing a pending hunt and its dependent content.
 */
class HuntDeletionAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_supprimer_chasse', [self::class, 'handle'], 10, 1);
    }

    public static function handle(): void {
        check_ajax_referer('hunt_management', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }

        $huntId = (int) ($_POST['chasse_id'] ?? 0);
        $userId = get_current_user_id();
        $service = new HuntDeletionService();
        $error = $service->getRequestError(
            $huntId,
            (string) get_post_type($huntId),
            (string) get_post_status($huntId),
            (string) get_post_meta($huntId, 'chasse_cache_statut', true),
            self::isAssociated($userId, $huntId)
        );
        if ($error !== null) {
            wp_send_json_error($error);
        }

        $deleted = self::trashHunt($huntId, $service);
        if (!$deleted) {
            wp_send_json_error('erreur_suppression');
        }

        self::addDeletedMessage($userId);
        wp_send_json_success(['redirect' => home_url('/mon-compte/organisateurs/')]);
    }

    public static function trashHunt(int $huntId, ?HuntDeletionService $service = null): bool {
        $riddleIds = get_posts((new HuntRiddleQueryService())->getRiddleIdsQueryArgs($huntId));
        $attachments = get_attached_media('image', $huntId);

        return ($service ?? new HuntDeletionService())->trash(
            $huntId,
            is_array($riddleIds) ? $riddleIds : [],
            is_array($attachments) ? $attachments : [],
            'wp_trash_post',
            static function (int $riddleId): void {
                $uploads = wp_upload_dir();
                (new RiddleUploadDirectoryService())->delete(
                    $riddleId,
                    (string) ($uploads['basedir'] ?? '')
                );
            },
            static function (int $huntId): void {
                update_post_meta($huntId, RiddleCacheMutationService::FIELD_NAME, []);
            }
        );
    }

    private static function isAssociated(int $userId, int $huntId): bool {
        if ($userId <= 0 || $huntId <= 0) {
            return false;
        }

        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(
            get_field('chasse_cache_organisateur', $huntId)
        );
        $users = $organizerId ? get_field('utilisateurs_associes', $organizerId) : [];

        return is_array($users)
            && in_array($userId, $relationships->normalizeIds($users), true);
    }

    private static function addDeletedMessage(int $userId): void {
        global $wpdb;

        (new AccountMessageService(new UserMessageRepository($wpdb)))->addFlash(
            $userId,
            [
                'text' => __(
                    'Votre chasse a été supprimée. Vous pouvez en créer une nouvelle quand vous le souhaitez.',
                    'chassesautresor-com'
                ),
                'type' => 'success',
                'dismissible' => true,
            ]
        );
    }
}
