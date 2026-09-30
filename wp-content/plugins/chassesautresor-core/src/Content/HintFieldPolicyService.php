<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Define the editable fields and normalization rules of a hint.
 */
class HintFieldPolicyService
{
    private const EDITABLE_FIELDS = [
        'indice_image',
        'indice_contenu',
        'indice_cible_type',
        'indice_enigme_linked',
        'indice_disponibilite',
        'indice_date_disponibilite',
        'indice_cout_points',
    ];

    private const CACHE_FIELDS = [
        'indice_image',
        'indice_contenu',
        'indice_disponibilite',
        'indice_date_disponibilite',
    ];

    public function isEditable(string $field): bool
    {
        return in_array($field, self::EDITABLE_FIELDS, true);
    }

    public function requiresCacheRefresh(string $field): bool
    {
        return in_array($field, self::CACHE_FIELDS, true);
    }

    public function normalizeTargetType(string $targetType): string
    {
        return $targetType === 'enigme' ? 'enigme' : 'chasse';
    }

    /**
     * @return int[]
     */
    public function normalizeRiddleIds(string $value): array
    {
        $ids = array_map('intval', explode(',', $value));
        $ids = array_filter($ids, static function (int $id): bool {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }
}
