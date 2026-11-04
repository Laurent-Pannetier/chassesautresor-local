<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

use ChassesAuTresor\Core\Content\RiddleStepGallery;

/**
 * Keep intermediate-step images under the protected _enigmes storage tree.
 */
final class RiddleStepImageStorageService
{
    public function __construct(
        private readonly ?RiddleImageProtectionService $protection = null
    ) {
    }

    public function isProtectedPath(string $relativePath, int $riddleId): bool
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $prefix = '_enigmes/enigme-' . $riddleId . '/';
        return str_starts_with($relativePath, $prefix);
    }

    public function targetDirectory(int $riddleId, string $uploadsBasedir): string
    {
        return rtrim(str_replace('\\', '/', $uploadsBasedir), '/')
            . '/_enigmes/enigme-' . $riddleId . '/etapes';
    }

    /**
     * Ensure the attachment lives under this riddle's protected folder.
     * Public uploads are moved in place; files from another protected folder are duplicated first.
     *
     * @return int attachment id to store on the step
     */
    public function ensureProtected(int $imageId, int $riddleId, int $stepId = 0): int
    {
        if ($imageId <= 0 || $riddleId <= 0) {
            return $imageId;
        }

        $relative = ltrim(str_replace('\\', '/', (string) get_post_meta($imageId, '_wp_attached_file', true)), '/');
        if ($relative !== '' && $this->isProtectedPath($relative, $riddleId)) {
            $this->names()->obscureAttachment($imageId);
            $this->protection()->protect($riddleId, false);
            return $imageId;
        }

        $workingId = $imageId;
        if ($relative !== '' && str_contains('/' . $relative, '/_enigmes/')) {
            // Already protected for another riddle — duplicate then move.
            $workingId = $this->duplicateAttachment($imageId, $stepId > 0 ? $stepId : $riddleId);
            if ($workingId <= 0) {
                return $imageId;
            }
        }

        if (!$this->moveAttachment($workingId, $riddleId)) {
            return $imageId;
        }

        $this->names()->obscureAttachment($workingId);
        $this->protection()->protect($riddleId, false);
        return $workingId;
    }

    /** @return int[] migrated step ids */
    public function migrateExisting(?callable $getField = null, ?callable $updateField = null): array
    {
        $getField = $getField ?? 'get_field';
        $updateField = $updateField ?? 'update_field';
        $steps = get_posts([
            'post_type' => 'enigme_etape',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);
        $migrated = [];
        foreach ($steps as $stepId) {
            $stepId = (int) $stepId;
            $imageIds = RiddleStepGallery::idsForStep($stepId, $getField);
            $riddleId = (int) $getField('etape_enigme_associee', $stepId);
            if ($imageIds === [] || $riddleId <= 0) {
                continue;
            }

            $securedIds = [];
            $changed = false;
            foreach ($imageIds as $imageId) {
                $securedId = $this->ensureProtected($imageId, $riddleId, $stepId);
                $finalId = $securedId > 0 ? $securedId : $imageId;
                if ($finalId !== $imageId) {
                    $changed = true;
                }
                $securedIds[] = $finalId;
            }
            if ($changed) {
                $updateField('etape_image', $securedIds, $stepId);
            }

            $protected = true;
            foreach ($securedIds as $finalId) {
                $relative = (string) get_post_meta($finalId, '_wp_attached_file', true);
                if (!$this->isProtectedPath($relative, $riddleId)) {
                    $protected = false;
                    break;
                }
            }
            if ($protected) {
                $migrated[] = $stepId;
            }
        }

        return $migrated;
    }

    /**
     * True when a step image is still outside this riddle's _enigmes folder.
     */
    public function hasPublicStepImage(?callable $getField = null): bool
    {
        if (!function_exists('get_posts') || !function_exists('get_post_meta')) {
            return false;
        }

        $getField = $getField ?? 'get_field';
        $placeholder = defined('ID_IMAGE_PLACEHOLDER_ENIGME') ? (int) ID_IMAGE_PLACEHOLDER_ENIGME : 0;
        $steps = get_posts([
            'post_type' => 'enigme_etape',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);
        foreach ($steps as $stepId) {
            $stepId = (int) $stepId;
            $riddleId = (int) $getField('etape_enigme_associee', $stepId);
            if ($riddleId <= 0) {
                continue;
            }
            foreach (RiddleStepGallery::idsForStep($stepId, $getField) as $imageId) {
                if ($imageId <= 0 || ($placeholder > 0 && $imageId === $placeholder)) {
                    continue;
                }
                $relative = (string) get_post_meta($imageId, '_wp_attached_file', true);
                if ($relative !== '' && !$this->isProtectedPath($relative, $riddleId)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Move a step gallery into protected storage and persist the new ids.
     *
     * Called from a save hook, never from a template.
     */
    public function persistProtectedGallery(
        int $stepId,
        int $riddleId,
        ?callable $getField = null,
        ?callable $updateField = null
    ): bool {
        if ($stepId <= 0 || $riddleId <= 0) {
            return false;
        }

        $getField = $getField ?? 'get_field';
        $updateField = $updateField ?? 'update_field';
        $placeholder = defined('ID_IMAGE_PLACEHOLDER_ENIGME') ? (int) ID_IMAGE_PLACEHOLDER_ENIGME : 0;
        $storedIds = [];
        $changed = false;
        foreach (RiddleStepGallery::idsForStep($stepId, $getField) as $imageId) {
            if ($imageId <= 0) {
                continue;
            }
            if ($placeholder > 0 && $imageId === $placeholder) {
                $storedIds[] = $imageId;
                continue;
            }

            $securedId = $this->ensureProtected($imageId, $riddleId, $stepId);
            $finalId = $securedId > 0 ? $securedId : $imageId;
            if ($finalId !== $imageId) {
                $changed = true;
            }
            $storedIds[] = $finalId;
        }

        if ($changed && $storedIds !== []) {
            $updateField('etape_image', $storedIds, $stepId);
        }

        return $changed;
    }

    private function protection(): RiddleImageProtectionService
    {
        return $this->protection ?? new RiddleImageProtectionService();
    }

    private function names(): RiddleImageObscureNameService
    {
        return new RiddleImageObscureNameService();
    }

    private function duplicateAttachment(int $imageId, int $parentId): int
    {
        $file = get_attached_file($imageId);
        if (!is_string($file) || $file === '' || !is_file($file)) {
            return 0;
        }

        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            return 0;
        }

        $destinationDir = trailingslashit((string) $uploads['path']);
        if (!wp_mkdir_p($destinationDir)) {
            return 0;
        }

        $basename = wp_unique_filename($destinationDir, basename($file));
        $destination = $destinationDir . $basename;
        if (!@copy($file, $destination)) {
            return 0;
        }

        $filetype = wp_check_filetype($basename);
        $attachmentId = wp_insert_attachment([
            'post_mime_type' => $filetype['type'] ?: 'image/jpeg',
            'post_title' => preg_replace('/\.[^.]+$/', '', $basename) ?: $basename,
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => max(0, $parentId),
        ], $destination, $parentId, true);

        if (is_wp_error($attachmentId) || !$attachmentId) {
            @unlink($destination);
            return 0;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $metadata = wp_generate_attachment_metadata((int) $attachmentId, $destination);
        if (is_array($metadata)) {
            wp_update_attachment_metadata((int) $attachmentId, $metadata);
        }

        return (int) $attachmentId;
    }

    private function moveAttachment(int $imageId, int $riddleId): bool
    {
        $file = get_attached_file($imageId);
        if (!is_string($file) || $file === '' || !is_file($file)) {
            return false;
        }

        $uploads = wp_upload_dir();
        $targetDir = $this->targetDirectory($riddleId, (string) $uploads['basedir']);
        if (!wp_mkdir_p($targetDir)) {
            return false;
        }

        $basename = basename($file);
        $destination = trailingslashit($targetDir) . $basename;
        if ($destination !== $file && !@rename($file, $destination)) {
            if (!@copy($file, $destination)) {
                return false;
            }
            @unlink($file);
        }

        $metadata = wp_get_attachment_metadata($imageId);
        $sourceDir = trailingslashit(dirname($file));
        if (is_array($metadata) && !empty($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size) {
                $sizeFile = isset($size['file']) ? (string) $size['file'] : '';
                if ($sizeFile === '') {
                    continue;
                }
                $from = $sourceDir . basename($sizeFile);
                $to = trailingslashit($targetDir) . basename($sizeFile);
                if (is_file($from) && $from !== $to) {
                    if (!@rename($from, $to)) {
                        @copy($from, $to);
                        @unlink($from);
                    }
                }
                $webpFrom = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $from);
                $webpTo = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $to);
                if (is_string($webpFrom) && is_string($webpTo) && is_file($webpFrom) && $webpFrom !== $webpTo) {
                    if (!@rename($webpFrom, $webpTo)) {
                        @copy($webpFrom, $webpTo);
                        @unlink($webpFrom);
                    }
                }
            }
        }

        $webpMainFrom = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $file);
        $webpMainTo = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $destination);
        if (is_string($webpMainFrom) && is_string($webpMainTo) && is_file($webpMainFrom) && $webpMainFrom !== $webpMainTo) {
            if (!@rename($webpMainFrom, $webpMainTo)) {
                @copy($webpMainFrom, $webpMainTo);
                @unlink($webpMainFrom);
            }
        }

        update_attached_file($imageId, $destination);
        if (is_array($metadata)) {
            wp_update_attachment_metadata($imageId, $metadata);
        }

        return true;
    }
}
