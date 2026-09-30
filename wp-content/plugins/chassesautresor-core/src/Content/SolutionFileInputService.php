<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Resolve the attachment selected or uploaded from a solution modal.
 */
class SolutionFileInputService {
    /**
     * @param array<string, mixed> $uploadedFile
     * @return array{id:int,submitted:bool,error:?string}
     */
    public function resolve(
        int $solutionId,
        array $uploadedFile,
        bool $hasPostedValue,
        int $postedValue,
        callable $upload,
        callable $isError,
        callable $getErrorMessage
    ): array {
        $hasUpload = !empty($uploadedFile['tmp_name']);
        $submitted = $hasPostedValue || $hasUpload;

        if ($hasUpload) {
            $attachmentId = $upload($solutionId);
            if ($isError($attachmentId)) {
                return [
                    'id' => 0,
                    'submitted' => true,
                    'error' => (string) $getErrorMessage($attachmentId),
                ];
            }

            return ['id' => (int) $attachmentId, 'submitted' => true, 'error' => null];
        }

        return [
            'id' => $hasPostedValue ? max(0, $postedValue) : 0,
            'submitted' => $submitted,
            'error' => null,
        ];
    }
}
