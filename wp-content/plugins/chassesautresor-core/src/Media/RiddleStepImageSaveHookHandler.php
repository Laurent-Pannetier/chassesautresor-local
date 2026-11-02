<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/** Persist protected step images when a step is saved, not when it is rendered. */
final class RiddleStepImageSaveHookHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('acf/save_post', [self::class, 'secureSavedStep'], 20);
        $addAction('save_post_enigme_etape', [self::class, 'secureSavedStep'], 20);
    }

    public static function secureSavedStep($postId): void
    {
        if (!is_numeric($postId) || !empty($GLOBALS['cta_securing_step_images'])) {
            return;
        }

        $stepId = (int) $postId;
        if ($stepId <= 0 || !function_exists('get_post_type') || get_post_type($stepId) !== 'enigme_etape') {
            return;
        }
        if (!function_exists('get_field')) {
            return;
        }

        $riddleId = (int) get_field('etape_enigme_associee', $stepId);
        if ($riddleId <= 0) {
            return;
        }

        $GLOBALS['cta_securing_step_images'] = true;
        try {
            (new RiddleStepImageStorageService())->persistProtectedGallery($stepId, $riddleId);
        } finally {
            unset($GLOBALS['cta_securing_step_images']);
        }
    }
}
