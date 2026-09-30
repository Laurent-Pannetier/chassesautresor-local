<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Validate and execute deletion of a hunt and its dependent content.
 */
class HuntDeletionService {
    public function getRequestError(
        int $huntId,
        string $postType,
        string $postStatus,
        string $huntStatus,
        bool $canDelete
    ): ?string {
        if ($huntId <= 0 || $postType !== 'chasse') {
            return 'id_invalide';
        }

        if ($postStatus !== 'pending' || $huntStatus !== 'revision') {
            return 'chasse_ineligible';
        }

        return $canDelete ? null : 'acces_refuse';
    }

    /**
     * @param array<int, int|string> $riddleIds
     * @param array<int, mixed> $attachments
     * @param callable(int): mixed $trashPost
     * @param callable(int): void $deleteRiddleFiles
     * @param callable(int): void $synchronizeRiddles
     */
    public function trash(
        int $huntId,
        array $riddleIds,
        array $attachments,
        callable $trashPost,
        callable $deleteRiddleFiles,
        callable $synchronizeRiddles
    ): bool {
        foreach ($riddleIds as $riddleId) {
            $riddleId = (int) $riddleId;
            if ($riddleId <= 0) {
                continue;
            }

            $trashPost($riddleId);
            $deleteRiddleFiles($riddleId);
        }

        $synchronizeRiddles($huntId);

        foreach ($attachments as $attachment) {
            $attachmentId = $this->attachmentId($attachment);
            if ($attachmentId > 0) {
                $trashPost($attachmentId);
            }
        }

        return (bool) $trashPost($huntId);
    }

    /** @param mixed $attachment */
    private function attachmentId($attachment): int {
        if (is_object($attachment) && isset($attachment->ID)) {
            return (int) $attachment->ID;
        }

        if (is_array($attachment) && isset($attachment['ID'])) {
            return (int) $attachment['ID'];
        }

        return 0;
    }
}
