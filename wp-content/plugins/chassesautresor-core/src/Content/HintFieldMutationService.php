<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use DateTimeInterface;

/**
 * Validate, normalize and persist a single editable hint field.
 */
class HintFieldMutationService {
    private HintFieldPolicyService $fieldPolicy;
    private HintStatusService $statusService;

    public function __construct(
        ?HintFieldPolicyService $fieldPolicy = null,
        ?HintStatusService $statusService = null
    ) {
        $this->fieldPolicy = $fieldPolicy ?? new HintFieldPolicyService();
        $this->statusService = $statusService ?? new HintStatusService();
    }

    /**
     * @param mixed $value
     * @param callable(string): string $sanitizeText
     * @param callable(string): string $sanitizeHtml
     * @param callable(string): ?DateTimeInterface $parseDate
     * @param callable(array<string, mixed>): mixed $updatePost
     * @param callable(string, mixed, int): mixed $updateField
     * @param callable(mixed): bool $isError
     * @return array{error:?string,refresh_cache:bool}
     */
    public function apply(
        int $hintId,
        string $field,
        $value,
        callable $sanitizeText,
        callable $sanitizeHtml,
        callable $parseDate,
        callable $updatePost,
        callable $updateField,
        callable $isError
    ): array {
        if ($field === 'post_title') {
            $updated = $updatePost([
                'ID' => $hintId,
                'post_title' => $sanitizeText((string) $value),
            ]);

            return [
                'error' => $isError($updated) ? 'echec_update_post_title' : null,
                'refresh_cache' => false,
            ];
        }

        if (!$this->fieldPolicy->isEditable($field)) {
            return ['error' => 'champ_inconnu', 'refresh_cache' => false];
        }

        $normalizedValue = $this->normalizeValue(
            $field,
            $value,
            $sanitizeHtml,
            $parseDate
        );
        if ($normalizedValue === null && $field === 'indice_date_disponibilite') {
            return ['error' => 'format_date_invalide', 'refresh_cache' => false];
        }

        $updated = $updateField($field, $normalizedValue, $hintId);
        if ($updated === false) {
            return ['error' => 'echec_mise_a_jour', 'refresh_cache' => false];
        }

        return [
            'error' => null,
            'refresh_cache' => $this->fieldPolicy->requiresCacheRefresh($field),
        ];
    }

    /**
     * @param mixed $value
     * @param callable(string): string $sanitizeHtml
     * @param callable(string): ?DateTimeInterface $parseDate
     * @return mixed
     */
    private function normalizeValue(
        string $field,
        $value,
        callable $sanitizeHtml,
        callable $parseDate
    ) {
        switch ($field) {
            case 'indice_image':
            case 'indice_cout_points':
                return (int) $value;
            case 'indice_contenu':
                return $sanitizeHtml((string) $value);
            case 'indice_cible_type':
                return $this->fieldPolicy->normalizeTargetType((string) $value);
            case 'indice_enigme_linked':
                return $this->fieldPolicy->normalizeRiddleIds((string) $value);
            case 'indice_disponibilite':
                return $this->statusService->normalizeAvailability((string) $value);
            case 'indice_date_disponibilite':
                $date = $parseDate((string) $value);

                return $date instanceof DateTimeInterface ? $date->format('Y-m-d H:i:s') : null;
        }

        return $value;
    }
}
