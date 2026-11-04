<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\ProtectedImagePathService;
use ChassesAuTresor\Core\Media\ProtectedRiddleImageDeliveryService;
use ChassesAuTresor\Core\Media\ProtectedThumbnailInlineService;
use ChassesAuTresor\Core\Media\RiddleImagePublicationGate;
use ChassesAuTresor\Core\Media\RiddleImagePublicUrlService;

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
     * Static file URL for a riddle or step image the current viewer may see.
     *
     * Empty when the image must stay out of the HTML. The attachment id stays
     * in the content; this address is built at display time.
     */
    function cta_voir_image_enigme_url(
        int $imageId,
        string $size = 'full',
        ?int $userId = null,
        string $format = ''
    ): string {
        $userId = $userId ?? (int) get_current_user_id();
        if (!(new RiddleImagePublicationGate())->mayPublishUrl($imageId, (int) $userId)) {
            return '';
        }

        return (new RiddleImagePublicUrlService())->url($imageId, $size, $format);
    }
}

if (!function_exists('cta_inline_riddle_thumbnail')) {
    /**
     * Inline HTML for a sommaire thumbnail.
     *
     * Empty when the file is too large: the caller then uses the static file
     * URL. This does not decide whether the viewer may see the enigma.
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
