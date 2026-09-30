<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Validate, store and attach a legacy PDF solution uploaded for a riddle.
 */
class RiddleSolutionUploadService {
    /**
     * @param array<string, mixed> $file
     * @return array{error:?string,message:?string,url:?string}
     */
    public function process(
        int $riddleId,
        array $file,
        callable $detectType,
        callable $upload,
        callable $attach,
        callable $isError,
        callable $getErrorMessage
    ): array {
        if ($file === [] || (int) ($file['error'] ?? 1) !== 0) {
            return $this->error('missing_file');
        }

        $fileType = $detectType($file);
        $extension = (string) ($fileType['ext'] ?? '');
        $mimeType = (string) ($fileType['type'] ?? '');
        $policyError = (new RiddleSolutionFilePolicyService())->getUploadError(
            (int) ($file['size'] ?? 0),
            $extension,
            $mimeType
        );
        if ($policyError !== null) {
            return $this->error($policyError);
        }

        $uploaded = $upload($file);
        if (!is_array($uploaded) || !isset($uploaded['url'], $uploaded['file'])) {
            return $this->error(
                'upload_failed',
                is_array($uploaded) ? (string) ($uploaded['error'] ?? '') : ''
            );
        }

        $attachmentId = $attach(
            $riddleId,
            (string) $uploaded['file'],
            (string) $file['name'],
            $mimeType
        );
        if ($isError($attachmentId)) {
            return $this->error('attachment_failed', (string) $getErrorMessage($attachmentId));
        }

        return ['error' => null, 'message' => null, 'url' => (string) $uploaded['url']];
    }

    /** @return array{error:string,message:?string,url:null} */
    private function error(string $code, ?string $message = null): array {
        return ['error' => $code, 'message' => $message, 'url' => null];
    }
}
