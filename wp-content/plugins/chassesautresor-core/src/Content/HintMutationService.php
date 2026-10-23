<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Persist fields submitted by the hint creation and edition modals.
 */
class HintMutationService {
    private HintStatusService $statusService;

    public function __construct(?HintStatusService $statusService = null) {
        $this->statusService = $statusService ?? new HintStatusService();
    }

    /**
     * @param callable(string, mixed, int): mixed $updateField
     * @param callable(string, int): mixed $deleteField
     * @param callable(int): void $refreshCache
     * @param array{prerequisites:int[],delay_minutes:int,anchor:string}|null $access
     * @return array{availability:string,availability_date:string}
     */
    public function applyModal(
        int $hintId,
        int $imageId,
        string $content,
        string $availability,
        string $submittedDate,
        string $existingDate,
        string $fallbackDate,
        bool $replaceOptionalFields,
        callable $updateField,
        callable $deleteField,
        callable $refreshCache,
        ?array $access = null
    ): array {
        if ($imageId > 0) {
            $updateField('indice_image', $imageId, $hintId);
        } elseif ($replaceOptionalFields) {
            $deleteField('indice_image', $hintId);
        }

        if ($replaceOptionalFields || $content !== '') {
            $updateField('indice_contenu', $content, $hintId);
        }

        $normalizedAvailability = $this->statusService->normalizeAvailability($availability);
        $availabilityDate = $this->statusService->resolveAvailabilityDate(
            $submittedDate,
            $existingDate,
            $fallbackDate
        );

        $updateField('indice_disponibilite', $normalizedAvailability, $hintId);
        $updateField('indice_date_disponibilite', $availabilityDate, $hintId);
        if (is_array($access)) {
            $updateField('indice_acces_pre_requis', array_values($access['prerequisites']), $hintId);
            $updateField('indice_delai_minutes', (int) $access['delay_minutes'], $hintId);
            $updateField('indice_delai_ancrage', (string) $access['anchor'], $hintId);
        }
        $refreshCache($hintId);

        return [
            'availability' => $normalizedAvailability,
            'availability_date' => $availabilityDate,
        ];
    }
}
