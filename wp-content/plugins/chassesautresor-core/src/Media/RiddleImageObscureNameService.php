<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Give a stored riddle image an unpredictable content-hash name.
 *
 * Derivatives (WordPress sizes, WebP siblings) keep the same stem, so the
 * name changes only when the original bytes change.
 */
final class RiddleImageObscureNameService
{
    public const HASH_LENGTH = 32;

    public function isUnpredictable(string $basename): bool
    {
        return (bool) preg_match(
            '/^[a-f0-9]{' . self::HASH_LENGTH . '}(?:-scaled)?(?:-\d+x\d+)?'
            . '\.(?:jpe?g|png|gif|webp)(?:\.webp)?$/i',
            $basename
        );
    }

    /**
     * Rename one file and the siblings that share its stem.
     *
     * Returns the original path when the name is already unpredictable, the
     * file is missing, or a destination already exists.
     */
    public function renameFamily(string $absoluteFile): string
    {
        if (!is_file($absoluteFile)) {
            return $absoluteFile;
        }

        $directory = dirname($absoluteFile);
        $basename = basename($absoluteFile);
        if ($this->isUnpredictable($basename)) {
            return $absoluteFile;
        }

        $hash = hash_file('sha256', $absoluteFile);
        if (!is_string($hash) || strlen($hash) < self::HASH_LENGTH) {
            return $absoluteFile;
        }

        $newStem = substr($hash, 0, self::HASH_LENGTH);
        $oldStem = (string) pathinfo($basename, PATHINFO_FILENAME);
        $extension = (string) pathinfo($basename, PATHINFO_EXTENSION);
        $planned = $this->planDirectory($directory, $basename, $oldStem, $newStem);
        if ($planned === [] || !isset($planned[$basename])) {
            return $absoluteFile;
        }

        foreach ($planned as $from => $to) {
            $source = $directory . '/' . $from;
            $target = $directory . '/' . $to;
            if (!is_file($target) || $source === $target) {
                continue;
            }
            $sourceHash = hash_file('sha256', $source);
            $targetHash = hash_file('sha256', $target);
            if (!is_string($sourceHash) || $sourceHash === '' || $sourceHash !== $targetHash) {
                return $absoluteFile;
            }
        }

        $done = [];
        foreach ($planned as $from => $to) {
            $source = $directory . '/' . $from;
            $target = $directory . '/' . $to;
            if (is_file($target)) {
                if ($source !== $target && is_file($source) && !@unlink($source)) {
                    return $absoluteFile;
                }
                continue;
            }
            if (!@rename($source, $target)) {
                foreach ($done as $rollbackFrom => $rollbackTo) {
                    @rename($rollbackTo, $rollbackFrom);
                }

                return $absoluteFile;
            }
            $done[$source] = $target;
        }

        return $directory . '/' . $newStem . '.' . $extension;
    }

    public function renamedBasename(
        string $name,
        string $oldBasename,
        string $oldStem,
        string $newStem
    ): ?string {
        if ($name === $oldBasename) {
            $extension = (string) pathinfo($name, PATHINFO_EXTENSION);

            return $newStem . ($extension === '' ? '' : '.' . $extension);
        }

        if (str_starts_with($name, $oldBasename . '.')) {
            $extension = (string) pathinfo($oldBasename, PATHINFO_EXTENSION);

            return $newStem . '.' . $extension . substr($name, strlen($oldBasename));
        }

        $stem = (string) pathinfo($name, PATHINFO_FILENAME);
        $extension = (string) pathinfo($name, PATHINFO_EXTENSION);
        $suffix = $extension === '' ? '' : '.' . $extension;
        if ($stem === $oldStem) {
            return $newStem . $suffix;
        }
        if ($oldStem !== '' && str_starts_with($stem, $oldStem . '-')) {
            return $newStem . substr($stem, strlen($oldStem)) . $suffix;
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $metadata
     * @return array<string, mixed>|null
     */
    public function obscureAttachment(int $attachmentId, ?array $metadata = null): ?array
    {
        if ($attachmentId <= 0 || !function_exists('get_attached_file')) {
            return $metadata;
        }

        $file = get_attached_file($attachmentId);
        if (!is_string($file) || $file === '' || !is_file($file)) {
            return $metadata;
        }

        $relative = function_exists('get_post_meta')
            ? (string) get_post_meta($attachmentId, '_wp_attached_file', true)
            : '';
        $normalized = str_replace('\\', '/', $relative);
        if (!str_contains($normalized, '_enigmes/')) {
            return $metadata;
        }
        if ($this->isUnpredictable(basename($file))) {
            return $metadata;
        }

        if ($metadata === null && function_exists('wp_get_attachment_metadata')) {
            $stored = wp_get_attachment_metadata($attachmentId);
            $metadata = is_array($stored) ? $stored : [];
        }
        $metadata = is_array($metadata) ? $metadata : [];

        $oldBasename = basename($file);
        $newAbsolute = $this->renameFamily($file);
        if ($newAbsolute === $file) {
            return $metadata;
        }

        if (function_exists('update_attached_file')) {
            update_attached_file($attachmentId, $newAbsolute);
        }

        $metadata = $this->rewriteMetadata(
            $metadata,
            $oldBasename,
            (string) pathinfo($oldBasename, PATHINFO_FILENAME),
            (string) pathinfo($newAbsolute, PATHINFO_FILENAME)
        );
        if (function_exists('wp_update_attachment_metadata')) {
            wp_update_attachment_metadata($attachmentId, $metadata);
        }
        $this->flushPathCache();

        return $metadata;
    }

    public function obscureRiddle(int $riddleId): void
    {
        if ($riddleId <= 0) {
            return;
        }

        global $wpdb;
        if (!isset($wpdb) || !is_object($wpdb) || !method_exists($wpdb, 'get_col')) {
            return;
        }

        $like = $wpdb->esc_like('_enigmes/enigme-' . $riddleId . '/') . '%';
        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} "
                . "WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s",
                $like
            )
        );
        foreach ((array) $ids as $id) {
            $this->obscureAttachment((int) $id);
        }
    }

    /**
     * Rename predictable files left on disk after attachment meta is updated.
     * Originals go first so their size and WebP siblings share the same stem.
     */
    public function obscureDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $this->renamePredictableOriginals($directory);
        $this->renameRemainingPredictable($directory);
    }

    public function riddleDirectoryIsUnpredictable(string $directory): bool
    {
        if (!is_dir($directory)) {
            return true;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $name = $file->getFilename();
            if ($name === '' || $name[0] === '.') {
                continue;
            }
            if (!preg_match('/\.(?:jpe?g|png|gif|webp)$/i', $name)) {
                continue;
            }
            if (!$this->isUnpredictable($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, string> old basename => new basename
     */
    private function planDirectory(
        string $directory,
        string $basename,
        string $oldStem,
        string $newStem
    ): array {
        $entries = scandir($directory);
        if ($entries === false) {
            return [];
        }

        $planned = [];
        foreach ($entries as $name) {
            if ($name === '.' || $name === '..' || !is_file($directory . '/' . $name)) {
                continue;
            }
            $renamed = $this->renamedBasename($name, $basename, $oldStem, $newStem);
            if ($renamed === null || $renamed === $name) {
                continue;
            }
            $planned[$name] = $renamed;
        }

        return $planned;
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function rewriteMetadata(
        array $metadata,
        string $oldBasename,
        string $oldStem,
        string $newStem
    ): array {
        if (isset($metadata['file']) && is_string($metadata['file'])) {
            $metadata['file'] = $this->replaceRelativeBasename(
                $metadata['file'],
                $oldBasename,
                $oldStem,
                $newStem
            );
        }
        if (isset($metadata['original_image']) && is_string($metadata['original_image'])) {
            $renamed = $this->renamedBasename(
                $metadata['original_image'],
                $oldBasename,
                $oldStem,
                $newStem
            );
            if ($renamed !== null) {
                $metadata['original_image'] = $renamed;
            }
        }
        if (!isset($metadata['sizes']) || !is_array($metadata['sizes'])) {
            return $metadata;
        }

        foreach ($metadata['sizes'] as $key => $size) {
            if (!is_array($size) || !isset($size['file']) || !is_string($size['file'])) {
                continue;
            }
            $renamed = $this->renamedBasename($size['file'], $oldBasename, $oldStem, $newStem);
            if ($renamed !== null) {
                $metadata['sizes'][$key]['file'] = $renamed;
            }
        }

        return $metadata;
    }

    private function replaceRelativeBasename(
        string $relative,
        string $oldBasename,
        string $oldStem,
        string $newStem
    ): string {
        $normalized = str_replace('\\', '/', $relative);
        $renamed = $this->renamedBasename(basename($normalized), $oldBasename, $oldStem, $newStem);
        if ($renamed === null) {
            return $relative;
        }
        $parent = dirname($normalized);
        if ($parent === '.' || $parent === '') {
            return $renamed;
        }

        return $parent . '/' . $renamed;
    }

    private function renamePredictableOriginals(string $directory): void
    {
        $paths = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $paths[] = $file->getPathname();
            }
        }
        usort($paths, static function (string $left, string $right): int {
            $leftWebp = preg_match('/\.webp$/i', $left) === 1;
            $rightWebp = preg_match('/\.webp$/i', $right) === 1;

            return $leftWebp <=> $rightWebp;
        });

        foreach ($paths as $path) {
            $name = basename($path);
            if ($name === '' || $name[0] === '.' || $this->isUnpredictable($name)) {
                continue;
            }
            if (!preg_match('/\.(?:jpe?g|png|gif|webp)$/i', $name)) {
                continue;
            }
            if (preg_match('/-\d+x\d+\.(?:jpe?g|png|gif|webp)$/i', $name)) {
                continue;
            }
            $this->renameFamily($path);
        }
    }

    private function renameRemainingPredictable(string $directory): void
    {
        $paths = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $paths[] = $file->getPathname();
            }
        }

        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }
            $name = basename($path);
            if ($name === '' || $name[0] === '.' || $this->isUnpredictable($name)) {
                continue;
            }
            if (!preg_match('/\.(?:jpe?g|png|gif|webp)$/i', $name)) {
                continue;
            }
            $this->renameFamily($path);
        }
    }

    private function flushPathCache(): void
    {
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group('trouver_chemin_image');
        }
    }
}
