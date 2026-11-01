<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Read the ordered comic pages stored on an intermediate step. */
final class RiddleStepGallery {
    /**
     * Accept a gallery, a legacy single attachment id, or an ACF image array.
     *
     * @param mixed $value
     * @return int[]
     */
    public static function normalize($value): array {
        if (is_numeric($value)) {
            $id = (int) $value;

            return $id > 0 ? [$id] : [];
        }
        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $id = (int) ($item['ID'] ?? $item['id'] ?? 0);
            } elseif (is_numeric($item)) {
                $id = (int) $item;
            } else {
                continue;
            }
            if ($id <= 0 || in_array($id, $ids, true)) {
                continue;
            }
            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * Pages in reading order. A gallery field may drop a legacy scalar id,
     * so the raw meta is read when the formatted value is empty.
     *
     * @return int[]
     */
    public static function idsForStep(int $stepId, ?callable $getField = null): array {
        if ($stepId <= 0) {
            return [];
        }

        $getField = $getField ?? 'get_field';
        $ids = self::normalize($getField('etape_image', $stepId));
        if ($ids !== [] || !function_exists('get_post_meta')) {
            return $ids;
        }

        return self::normalize(get_post_meta($stepId, 'etape_image', true));
    }
}
