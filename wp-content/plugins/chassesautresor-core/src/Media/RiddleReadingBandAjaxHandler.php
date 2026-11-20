<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * AJAX adapter that finishes the bands skipped by the first response.
 */
final class RiddleReadingBandAjaxHandler
{
    public static function register(callable $addAction): void
    {
        $action = 'wp_ajax_' . RiddleReadingBandPreparation::ACTION;
        $addAction($action, [self::class, 'prepare']);
        $addAction('wp_ajax_nopriv_' . RiddleReadingBandPreparation::ACTION, [self::class, 'prepare']);
    }

    public static function prepare(): void
    {
        $verified = function_exists('check_ajax_referer')
            && check_ajax_referer(RiddleReadingBandPreparation::ACTION, 'nonce', false);
        if (!$verified) {
            wp_send_json_error(['message' => 'invalid_nonce'], 403);
        }

        if (function_exists('set_time_limit')) {
            set_time_limit(120);
        }

        $ids = isset($_POST['image_ids']) && is_array($_POST['image_ids'])
            ? $_POST['image_ids']
            : [];
        $userId = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
        $gate = new RiddleImagePublicationGate();
        $service = new RiddleReadingBandService();
        $ready = (new RiddleReadingBandPreparation())->prepare(
            $ids,
            static fn (int $imageId): bool => $gate->mayPublishUrl($imageId, $userId),
            static function (int $imageId): string {
                if (!function_exists('get_attached_file')) {
                    return '';
                }
                $file = get_attached_file($imageId);

                return is_string($file) ? $file : '';
            },
            static fn (string $file): array => $service->ensure($file)
        );

        wp_send_json_success(['image_ids' => $ready]);
    }
}
