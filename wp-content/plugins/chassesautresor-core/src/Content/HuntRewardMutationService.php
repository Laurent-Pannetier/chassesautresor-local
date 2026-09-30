<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Validate and persist the editable reward fields of a hunt.
 */
class HuntRewardMutationService {
    private const FIELD_MAP = [
        'caracteristiques.chasse_infos_recompense_valeur' => 'chasse_infos_recompense_valeur',
        'caracteristiques.chasse_infos_recompense_texte' => 'chasse_infos_recompense_texte',
        'caracteristiques.chasse_infos_recompense_titre' => 'chasse_infos_recompense_titre',
        'chasse_infos_recompense_valeur' => 'chasse_infos_recompense_valeur',
        'chasse_infos_recompense_texte' => 'chasse_infos_recompense_texte',
        'chasse_infos_recompense_titre' => 'chasse_infos_recompense_titre',
    ];

    /**
     * @param mixed $value
     * @param callable(string, mixed, int): mixed $updateField
     * @return array{handled:bool,error:?string,recalculate_status:bool}
     */
    public function apply(int $huntId, string $field, $value, callable $updateField): array {
        if (!isset(self::FIELD_MAP[$field])) {
            return ['handled' => false, 'error' => null, 'recalculate_status' => false];
        }

        $storedField = self::FIELD_MAP[$field];
        if ($storedField === 'chasse_infos_recompense_valeur') {
            if (!is_numeric($value) || (float) $value <= 0 || (float) $value > 5000000) {
                return ['handled' => true, 'error' => 'valeur_invalide', 'recalculate_status' => false];
            }
        }

        if ($updateField($storedField, $value, $huntId) === false) {
            return ['handled' => true, 'error' => 'echec_mise_a_jour', 'recalculate_status' => false];
        }

        return [
            'handled' => true,
            'error' => null,
            'recalculate_status' => true,
        ];
    }
}
