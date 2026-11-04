<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

use ReflectionProperty;

/**
 * Keep a 1380×4000 riddle strip at its drawn size.
 *
 * WordPress 7.1 scales the long side to 2560, sometimes in the browser, and
 * Imagify can do the same when “resize large images” is on. A strip would
 * become about 883×2560. This guard refuses both reductions for riddle uploads
 * and keeps the unscaled file when a “-scaled” replacement is proposed.
 */
class RiddleStripUploadGuard {
    public const STRIP_WIDTH = 1380;

    public const STRIP_HEIGHT = 4000;

    private const FIELD_VISUAL = 'enigme_visuel_image';

    private const FIELD_STEP = 'etape_image';

    private bool $uploadActive = false;

    private bool $suppressResize = false;

    public static function register(callable $addAction, callable $addFilter): void {
        $guard = new self();
        $addFilter('big_image_size_threshold', [$guard, 'threshold'], PHP_INT_MAX, 4);
        $addFilter('update_attached_file', [$guard, 'keepFullFile'], PHP_INT_MAX, 2);
        $addFilter('pre_get_imagify_option_resize_larger', [$guard, 'skipImagifyResize'], 10, 2);
        $addFilter('acf/upload_prefilter/name=' . self::FIELD_VISUAL, [$guard, 'beginFieldUpload'], 5, 3);
        $addFilter('acf/upload_prefilter/name=' . self::FIELD_STEP, [$guard, 'beginFieldUpload'], 5, 3);
        $addFilter('acf/upload_file/name=' . self::FIELD_STEP, [$guard, 'finishFieldUpload'], 20, 1);
        $addFilter('wp_handle_upload_prefilter', [$guard, 'beginRequestUpload'], 9, 1);
        $addFilter('imagify_before_optimize_size', [$guard, 'beforeImagifySize'], 10, 3);
        $addAction('imagify_after_optimize_size', [$guard, 'afterImagifySize'], 10, 0);
        $addAction('shutdown', [$guard, 'endStripUpload'], 10, 0);
    }

    /**
     * @param mixed $threshold
     * @param mixed $imagesize
     * @param mixed $file
     * @param mixed $attachmentId
     * @return mixed
     */
    public function threshold($threshold, $imagesize = null, $file = '', $attachmentId = 0) {
        if ($this->protects((string) $file, (int) $attachmentId)) {
            return false;
        }

        return $threshold;
    }

    /**
     * @param mixed $file
     * @param mixed $attachmentId
     */
    public function keepFullFile($file, $attachmentId = 0): string {
        $file = (string) $file;
        $full = $this->unscaledSibling($file);
        if ($full === null) {
            return $file;
        }
        if (!$this->protects($full, (int) $attachmentId) && !$this->protects($file, (int) $attachmentId)) {
            return $file;
        }
        if (is_file($file) && $file !== $full) {
            unlink($file);
        }

        return $full;
    }

    /**
     * @param mixed $value
     * @param mixed $default
     * @return mixed
     */
    public function skipImagifyResize($value, $default = null) {
        if (!$this->uploadActive && !$this->suppressResize) {
            return null;
        }
        $this->disarmImagifyResizeCache();

        return 0;
    }

    /**
     * @param mixed $errors
     * @param mixed $file
     * @param mixed $field
     * @return mixed
     */
    public function beginFieldUpload($errors, $file = null, $field = null) {
        $this->beginStripUpload();
        $name = is_array($field) ? (string) ($field['name'] ?? '') : '';
        if ($name === self::FIELD_STEP) {
            RiddleImageProtectionLifecycle::beginUpload($errors, $file, $field);
        }

        return $errors;
    }

    /**
     * @param mixed $file
     * @return mixed
     */
    public function finishFieldUpload($file) {
        RiddleImageProtectionLifecycle::finishUpload($file);

        return $file;
    }

    /**
     * @param mixed $file
     * @return mixed
     */
    public function beginRequestUpload($file) {
        if ($this->requestParentIsRiddle() || $this->requestFieldIsStrip()) {
            $this->beginStripUpload();
        }

        return $file;
    }

    /**
     * @param mixed $response
     * @param mixed $process
     * @param mixed $file
     * @return mixed
     */
    public function beforeImagifySize($response, $process = null, $file = null) {
        $path = '';
        if (is_object($file) && method_exists($file, 'get_path')) {
            $path = (string) $file->get_path();
        } elseif (is_string($file)) {
            $path = $file;
        }
        $attachmentId = 0;
        if (is_object($process) && method_exists($process, 'get_media')) {
            $media = $process->get_media();
            if (is_object($media) && method_exists($media, 'get_id')) {
                $attachmentId = (int) $media->get_id();
            }
        }
        if ($this->protects($path, $attachmentId)) {
            $this->suppressResize = true;
            $this->disarmImagifyResizeCache();
        }

        return $response;
    }

    public function afterImagifySize(): void {
        if (!$this->uploadActive) {
            $this->suppressResize = false;
        }
    }

    public function beginStripUpload(): void {
        $this->uploadActive = true;
        $this->suppressResize = true;
        $this->disarmImagifyResizeCache();
    }

    public function endStripUpload(): void {
        $this->uploadActive = false;
        $this->suppressResize = false;
    }

    public function shouldScale(string $file, $incomingThreshold = 2560): bool {
        $size = @getimagesize($file);
        if (!is_array($size)) {
            return false;
        }
        $threshold = $this->threshold($incomingThreshold, $size, $file, 0);
        if ($threshold === false || (int) $threshold <= 0) {
            return false;
        }
        $limit = (int) $threshold;

        return (int) $size[0] > $limit || (int) $size[1] > $limit;
    }

    public function preserveFile(string $source, string $destination): string {
        $directory = dirname($destination);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        if ($source !== $destination) {
            copy($source, $destination);
        }

        return $destination;
    }

    public function protects(string $file, int $attachmentId = 0): bool {
        if ($this->uploadActive || $this->suppressResize) {
            return true;
        }
        if ($this->pathIsStrip($file)) {
            return true;
        }
        if ($attachmentId > 0 && function_exists('get_post_meta')) {
            $relative = (string) get_post_meta($attachmentId, '_wp_attached_file', true);
            if ($this->pathIsStrip($relative)) {
                return true;
            }
        }
        if ($attachmentId > 0 && $this->attachmentParentIsRiddle($attachmentId)) {
            return true;
        }

        return $this->requestParentIsRiddle();
    }

    public function unscaledSibling(string $file): ?string {
        $base = basename($file);
        if (!preg_match('/^(.+)-scaled(\.[^.]+)$/', $base, $match)) {
            return null;
        }
        $sibling = dirname($file) . '/' . $match[1] . $match[2];

        return is_file($sibling) ? $sibling : null;
    }

    private function pathIsStrip(string $file): bool {
        return str_contains(str_replace('\\', '/', $file), '/_enigmes/');
    }

    private function requestParentIsRiddle(): bool {
        if (!function_exists('get_post_type')) {
            return false;
        }
        foreach (['post_id', 'post'] as $key) {
            if (!isset($_REQUEST[$key])) {
                continue;
            }
            $postId = (int) $_REQUEST[$key];
            if ($postId > 0 && $this->postTypeIsRiddle((string) get_post_type($postId))) {
                return true;
            }
        }

        return false;
    }

    private function requestFieldIsStrip(): bool {
        $names = [self::FIELD_VISUAL, self::FIELD_STEP, 'field_etape_image'];
        foreach (['_acfuploader', 'acf_field'] as $key) {
            if (!isset($_REQUEST[$key])) {
                continue;
            }
            if (in_array((string) $_REQUEST[$key], $names, true)) {
                return true;
            }
        }

        return false;
    }

    private function attachmentParentIsRiddle(int $attachmentId): bool {
        if (!function_exists('get_post') || !function_exists('get_post_type')) {
            return false;
        }
        $post = get_post($attachmentId);
        $parent = is_object($post) ? (int) ($post->post_parent ?? 0) : 0;
        if ($parent <= 0) {
            return false;
        }

        return $this->postTypeIsRiddle((string) get_post_type($parent));
    }

    private function postTypeIsRiddle(string $type): bool {
        return $type === 'enigme' || $type === 'enigme_etape';
    }

    private function disarmImagifyResizeCache(): void {
        if (!function_exists('imagify_get_context')) {
            return;
        }
        $context = imagify_get_context('wp');
        if (!is_object($context)) {
            return;
        }
        try {
            $property = new ReflectionProperty($context, 'resizing_threshold');
            $property->setValue($context, 0);
        } catch (\ReflectionException $exception) {
            return;
        }
    }
}
