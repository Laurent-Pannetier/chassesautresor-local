<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

use ChassesAuTresor\Core\Content\RiddleAccessService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Resolve protected riddle-image access and filesystem locations without theme adapters. */
final class ProtectedRiddleAssetService
{
    public function canViewRiddle(int $riddleId, int $userId): bool
    {
        global $wpdb;

        if (get_post_type($riddleId) !== 'enigme') {
            return false;
        }

        $relationships = new RelationshipService();
        $huntId = $relationships->normalizeId(get_field('enigme_chasse_associee', $riddleId));
        $administrator = $userId > 0 && user_can($userId, 'manage_options');
        if ($administrator || $huntId === null) {
            return (new RiddleAccessService())->canView(
                $administrator,
                false,
                false,
                '',
                '',
                '',
                false,
                false,
                false
            );
        }

        $huntFinished = get_field('chasse_cache_statut', $huntId) === 'termine';
        $engaged = CoreServiceFactory::huntEngagement($wpdb)->isEngaged($userId, $huntId);
        $user = $userId > 0 ? get_userdata($userId) : false;
        $roles = $user ? (array) $user->roles : [];
        $subscriber = in_array('abonne', $roles, true);
        $organizer = (!$subscriber || $engaged) && $this->isAssociatedOrganizer($userId, $huntId);

        return (new RiddleAccessService())->canView(
            false,
            true,
            $huntFinished,
            (string) get_post_status($riddleId),
            (string) get_field('enigme_cache_etat_systeme', $riddleId),
            (string) get_field('chasse_cache_statut_validation', $huntId),
            $organizer,
            $engaged,
            $subscriber
        );
    }

    /** @return array{path:string,mime:string}|null */
    public function findImage(int $imageId, string $size = 'full'): ?array
    {
        $source = wp_get_attachment_image_src($imageId, $size === 'full' ? 'full' : $size);
        $url = is_array($source) ? ($source[0] ?? '') : '';
        if ($url === '') {
            return null;
        }

        $uploads = wp_get_upload_dir();
        $path = str_replace((string) $uploads['baseurl'], (string) $uploads['basedir'], $url);
        $webpPath = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $path);
        if (is_string($webpPath) && $webpPath !== $path && is_file($webpPath)) {
            return ['path' => $webpPath, 'mime' => 'image/webp'];
        }

        if (is_file($path)) {
            $mime = wp_check_filetype($path)['type'] ?? 'application/octet-stream';
            return ['path' => $path, 'mime' => $mime];
        }

        return $size === 'full' ? null : $this->findImage($imageId);
    }

    private function isAssociatedOrganizer(int $userId, int $huntId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        if ($organizerId === null) {
            return false;
        }

        $userIds = $relationships->normalizeIds((array) get_field('utilisateurs_associes', $organizerId));
        return in_array($userId, $userIds, true);
    }
}
