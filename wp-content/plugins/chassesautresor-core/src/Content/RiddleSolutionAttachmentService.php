<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Persist and remove legacy PDF attachments associated with riddles.
 */
class RiddleSolutionAttachmentService {
    /** @return array<string, string> */
    public function getAttachmentData(string $originalName, string $mimeType): array {
        return [
            'post_mime_type' => $mimeType,
            'post_title' => sanitize_file_name($originalName),
            'post_content' => '',
            'post_status' => 'inherit',
        ];
    }

    /**
     * @return int|\WP_Error
     */
    public function attach(
        int $riddleId,
        string $filePath,
        string $originalName,
        string $mimeType
    ) {
        if (get_post_type($riddleId) !== 'enigme') {
            return new \WP_Error(
                'invalid_riddle',
                __('ID de post invalide.', 'chassesautresor-com')
            );
        }

        $attachmentId = wp_insert_attachment(
            $this->getAttachmentData($originalName, $mimeType),
            $filePath,
            $riddleId
        );
        if (is_wp_error($attachmentId)) {
            return $attachmentId;
        }

        update_field('enigme_solution_fichier', $attachmentId, $riddleId);

        return (int) $attachmentId;
    }

    public function remove(int $riddleId): bool {
        if (get_post_type($riddleId) !== 'enigme') {
            return false;
        }

        $attachmentId = (new RelationshipService())->normalizeId(
            get_field('enigme_solution_fichier', $riddleId, false)
        );
        if ($attachmentId !== null) {
            wp_delete_attachment($attachmentId, true);
        }

        update_field('enigme_solution_fichier', null, $riddleId);

        return true;
    }
}
