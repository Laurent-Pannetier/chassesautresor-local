<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\ProtectedImagePathService;
use ChassesAuTresor\Core\Media\ProtectedRiddleImageSignedUrlService;

if (!function_exists('trouver_chemin_image')) {
    /**
     * @return array{path: string, mime: string}|null
     */
    function trouver_chemin_image(int $imageId, string $size = 'full'): ?array
    {
        return (new ProtectedImagePathService())->find($imageId, $size);
    }
}

if (!function_exists('cta_voir_image_enigme_url')) {
    /**
     * Build a signed proxy URL for a protected riddle/step image.
     */
    function cta_voir_image_enigme_url(int $imageId, string $size = 'full', ?int $userId = null): string
    {
        $userId = $userId ?? get_current_user_id();
        $service = new ProtectedRiddleImageSignedUrlService();

        return $service->buildUrl(
            $imageId,
            $size,
            (int) $userId,
            site_url('/voir-image-enigme')
        );
    }
}
