<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Media\RiddleUploadDirectoryService;

/**
 * Permanently delete a riddle and clean up its private upload directory.
 */
class RiddleDeletionService {
    public function supportsPostType(string $postType): bool {
        return $postType === 'enigme';
    }

    public function delete(int $riddleId, string $uploadsBaseDirectory): bool {
        if (!$this->supportsPostType((string) get_post_type($riddleId))) {
            return false;
        }

        if (wp_delete_post($riddleId, true) === false) {
            return false;
        }

        (new RiddleUploadDirectoryService())->delete($riddleId, $uploadsBaseDirectory);

        return true;
    }
}
