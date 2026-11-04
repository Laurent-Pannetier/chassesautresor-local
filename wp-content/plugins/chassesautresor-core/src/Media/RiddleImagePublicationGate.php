<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/**
 * Decide whether the current viewer may receive a direct riddle-image URL.
 *
 * An ordinary attachment (placeholder, other galleries) keeps its public URL.
 * A riddle or step image stays out of the HTML unless the same access check
 * the old proxy applied would have allowed it.
 */
final class RiddleImagePublicationGate
{
    /**
     * @param (callable(int): bool)|null $linked
     * @param (callable(int, int): bool)|null $viewer
     */
    public function __construct(
        private $linked = null,
        private $viewer = null
    ) {
    }

    public function mayPublishUrl(int $imageId, int $userId): bool
    {
        if ($imageId <= 0) {
            return false;
        }

        $linked = $this->linked ?? [$this, 'isLinkedRiddleImage'];
        if (!$linked($imageId)) {
            return true;
        }

        $viewer = $this->viewer ?? [$this, 'canViewLinkedImage'];

        return (bool) $viewer($imageId, $userId);
    }

    public function isLinkedRiddleImage(int $imageId): bool
    {
        if ($imageId <= 0) {
            return false;
        }

        $relative = function_exists('get_post_meta')
            ? (string) get_post_meta($imageId, '_wp_attached_file', true)
            : '';
        $normalized = '/' . ltrim(str_replace('\\', '/', $relative), '/');
        if (str_contains($normalized, '/_enigmes/')) {
            return true;
        }

        global $wpdb;
        if (!isset($wpdb)) {
            return false;
        }

        if (CoreServiceFactory::riddleImages($wpdb)->findRiddleId($imageId)) {
            return true;
        }

        return CoreServiceFactory::riddleStepImages($wpdb)->findContext($imageId) !== null;
    }

    public function canViewLinkedImage(int $imageId, int $userId): bool
    {
        global $wpdb;
        $images = CoreServiceFactory::riddleImages($wpdb);
        $steps = CoreServiceFactory::riddleStepImages($wpdb);
        $riddleId = $images->findRiddleId($imageId);
        $context = $steps->findContext($imageId);
        if (!$riddleId && $context === null) {
            return false;
        }

        $allowed = false;
        if ($riddleId) {
            $allowed = (new ProtectedRiddleAssetService())->canViewRiddle((int) $riddleId, $userId);
        }
        if (!$allowed && $context !== null) {
            $allowed = $steps->canView($imageId, $userId);
        }

        return $allowed;
    }
}
