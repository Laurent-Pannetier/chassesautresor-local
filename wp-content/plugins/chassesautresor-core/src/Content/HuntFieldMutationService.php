<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use DateTimeInterface;

/**
 * Normalize and persist standard editable hunt fields.
 */
class HuntFieldMutationService {
    /**
     * @param mixed $value
     * @param callable(string, array<int, string>): ?DateTimeInterface $parseDate
     * @param callable(string): string $sanitizeText
     * @param callable(string, mixed, int): mixed $updateField
     * @return array{handled:bool,error:?string,recalculate_status:bool}
     */
    public function apply(
        int $huntId,
        string $field,
        $value,
        callable $parseDate,
        callable $sanitizeText,
        callable $updateField
    ): array {
        $storedField = null;
        $normalizedValue = $value;
        $recalculateStatus = false;

        switch ($field) {
            case 'caracteristiques.chasse_infos_date_debut':
                $date = $parseDate((string) $value, ['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i']);
                if (!$date instanceof DateTimeInterface) {
                    return $this->error('format_date_invalide');
                }
                $storedField = 'chasse_infos_date_debut';
                $normalizedValue = $date->format('Y-m-d H:i:s');
                $recalculateStatus = true;
                break;
            case 'caracteristiques.chasse_infos_date_fin':
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
                    return $this->error('format_date_invalide');
                }
                $storedField = 'chasse_infos_date_fin';
                $recalculateStatus = true;
                break;
            case 'caracteristiques.chasse_infos_duree_illimitee':
                $storedField = 'chasse_infos_duree_illimitee';
                $normalizedValue = (int) $value;
                $recalculateStatus = true;
                break;
            case 'caracteristiques.chasse_infos_cout_points':
                $storedField = 'chasse_infos_cout_points';
                $normalizedValue = (int) $value;
                $recalculateStatus = true;
                break;
            case 'caracteristiques.chasse_infos_nb_max_gagants':
                $storedField = 'chasse_infos_nb_max_gagants';
                $normalizedValue = (int) $value;
                break;
            case 'champs_caches.chasse_cache_gagnants':
                $storedField = 'chasse_cache_gagnants';
                break;
            case 'champs_caches.chasse_cache_date_decouverte':
            case 'chasse_cache_date_decouverte':
                $date = $parseDate((string) $value, ['Y-m-d H:i:s', 'Y-m-d H:i']);
                if (!$date instanceof DateTimeInterface) {
                    return $this->error('format_date_invalide');
                }
                $storedField = 'chasse_cache_date_decouverte';
                $normalizedValue = $date->format('Y-m-d H:i:s');
                $recalculateStatus = true;
                break;
            case 'champs_caches.chasse_cache_statut_validation':
            case 'chasse_cache_statut_validation':
                $storedField = 'chasse_cache_statut_validation';
                $normalizedValue = $sanitizeText((string) $value);
                $recalculateStatus = true;
                break;
            default:
                return ['handled' => false, 'error' => null, 'recalculate_status' => false];
        }

        if ($updateField($storedField, $normalizedValue, $huntId) === false) {
            return $this->error('echec_mise_a_jour');
        }

        return [
            'handled' => true,
            'error' => null,
            'recalculate_status' => $recalculateStatus,
        ];
    }

    /**
     * @return array{handled:bool,error:string,recalculate_status:bool}
     */
    private function error(string $error): array {
        return ['handled' => true, 'error' => $error, 'recalculate_status' => false];
    }
}
