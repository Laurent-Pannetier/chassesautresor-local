<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/** Prefill the hunt relationship shown on the native hint editing screen. */
final class HintRelationshipFieldHookHandler
{
    public static function register(callable $addFilter): void
    {
        $addFilter(
            'acf/load_field/name=indice_chasse_linked',
            [self::class, 'prefillLinkedHunt']
        );
    }

    public static function prefillLinkedHunt(array $field): array
    {
        global $post;

        if (!$post || get_post_type($post->ID) !== 'indice') {
            return $field;
        }

        $relationships = new RelationshipService();
        if ($relationships->normalizeId(get_post_meta($post->ID, 'indice_chasse_linked', true)) !== null) {
            return $field;
        }

        $targetType = (string) get_field('indice_cible_type', $post->ID);
        $huntId = null;
        if ($targetType === 'chasse') {
            $huntId = $relationships->normalizeId($_GET['chasse_id'] ?? null);
        } elseif ($targetType === 'enigme') {
            $riddleId = $relationships->normalizeId(get_field('indice_enigme_linked', $post->ID));
            if ($riddleId !== null) {
                $huntId = $relationships->normalizeId(
                    get_field('enigme_chasse_associee', $riddleId)
                );
            }
        }

        if ($huntId !== null) {
            $field['value'] = $huntId;
        }

        return $field;
    }
}
