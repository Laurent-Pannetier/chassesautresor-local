<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\ProtectedImagePathService;

if (!function_exists('trouver_chemin_image')) {
    /**
     * @return array{path: string, mime: string}|null
     */
    function trouver_chemin_image(int $imageId, string $size = 'full'): ?array
    {
        return (new ProtectedImagePathService())->find($imageId, $size);
    }
}
