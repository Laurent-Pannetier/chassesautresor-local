<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\ProtectedImagePathService;
use ChassesAuTresor\Core\Media\ProtectedRiddleImageDeliveryService;
use ChassesAuTresor\Core\Media\ProtectedRiddleImageSignedUrlService;
use ChassesAuTresor\Core\Media\ProtectedThumbnailInlineService;

if (!function_exists('trouver_chemin_image')) {
    /**
     * @return array{path: string, mime: string}|null
     */
    function trouver_chemin_image(int $imageId, string $size = 'full', string $format = ''): ?array
    {
        return (new ProtectedImagePathService())->find($imageId, $size, $format);
    }
}

if (!function_exists('cta_voir_image_enigme_url')) {
    /**
     * Build a signed proxy URL for a protected riddle/step image.
     */
    function cta_voir_image_enigme_url(
        int $imageId,
        string $size = 'full',
        ?int $userId = null,
        string $format = ''
    ): string {
        $userId = $userId ?? get_current_user_id();
        $service = new ProtectedRiddleImageSignedUrlService();

        return $service->buildUrl(
            $imageId,
            $size,
            (int) $userId,
            site_url('/voir-image-enigme'),
            null,
            $format
        );
    }
}

if (!function_exists('cta_inline_riddle_thumbnail')) {
    /**
     * Inline HTML for a sommaire thumbnail.
     *
     * Empty when the file must stay on the signed proxy. This does not decide
     * whether the viewer may see the enigma: the page that calls it already has.
     */
    function cta_inline_riddle_thumbnail(int $imageId, string $alt = ''): string
    {
        $service = new ProtectedThumbnailInlineService(
            static function (int $id, string $size, string $format): ?array {
                return (new ProtectedImagePathService())->find($id, $size, $format);
            },
            new ProtectedRiddleImageDeliveryService()
        );

        return $service->html($imageId, $alt);
    }
}
