<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Move a legacy riddle solution PDF into public storage after hunt completion.
 */
class RiddleSolutionFilePublicationService {
    public static function publish(int $riddleId): void {
        if (get_post_type($riddleId) !== 'enigme') {
            return;
        }

        $attachmentId = (new RelationshipService())->normalizeId(
            get_field('enigme_solution_fichier', $riddleId, false)
        );
        if ($attachmentId === null) {
            return;
        }

        $sourcePath = get_attached_file($attachmentId);
        if (!$sourcePath || !is_file($sourcePath)) {
            return;
        }

        $huntId = (new RelationshipService())->normalizeId(
            get_field('enigme_chasse_associee', $riddleId)
        );
        if ($huntId === null || get_post_type($huntId) !== 'chasse') {
            return;
        }

        $cachedFields = get_field('champs_caches', $huntId);
        $huntStatus = is_array($cachedFields)
            ? (string) ($cachedFields['chasse_cache_statut'] ?? '')
            : '';
        if ($huntStatus === '') {
            $huntStatus = (string) get_field('chasse_cache_statut', $huntId);
        }
        if (trim(strtolower($huntStatus)) !== 'termine') {
            return;
        }

        $targetPath = self::moveFile(
            $sourcePath,
            WP_CONTENT_DIR . '/uploads/solutions-publiques'
        );
        if ($targetPath !== null) {
            update_attached_file($attachmentId, $targetPath);
        }
    }

    public static function moveFile(string $sourcePath, string $targetDirectory): ?string {
        if (!is_file($sourcePath)) {
            return null;
        }

        if (!is_dir($targetDirectory) && !wp_mkdir_p($targetDirectory)) {
            return null;
        }

        $targetPath = rtrim($targetDirectory, '/\\') . '/' . basename($sourcePath);
        if (file_exists($targetPath)) {
            return null;
        }

        if (@rename($sourcePath, $targetPath)) {
            return $targetPath;
        }

        if (!@copy($sourcePath, $targetPath) || !@unlink($sourcePath)) {
            @unlink($targetPath);
            return null;
        }

        return $targetPath;
    }
}
