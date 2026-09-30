<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Persist the small set of editable hunt fields that do not need dedicated rules.
 */
class HuntGenericFieldMutationService {
    private const ALLOWED_FIELDS = [
        'chasse_principale_image',
        'chasse_principale_description',
        'chasse_mode_fin',
    ];

    /**
     * @param mixed $value
     * @param callable(string, mixed, int): mixed $updateField
     * @param callable(int, string, bool): mixed $getPostMeta
     * @param callable(mixed): mixed $stripSlashes
     * @return array{handled:bool,error:?string}
     */
    public function apply(
        int $huntId,
        string $field,
        $value,
        callable $updateField,
        callable $getPostMeta,
        callable $stripSlashes
    ): array {
        if (!in_array($field, self::ALLOWED_FIELDS, true)) {
            return ['handled' => false, 'error' => 'champ_non_autorise'];
        }

        $normalizedValue = is_numeric($value) ? (int) $value : $value;
        $updated = $updateField($field, $normalizedValue, $huntId);
        $storedValue = $getPostMeta($huntId, $field, true);
        $comparedValue = $stripSlashes($value);

        if (!$updated && trim((string) $storedValue) !== trim((string) $comparedValue)) {
            return ['handled' => true, 'error' => 'echec_mise_a_jour'];
        }

        return ['handled' => true, 'error' => null];
    }
}
