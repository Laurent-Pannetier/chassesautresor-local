<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Define validation and defaults for newly created solutions.
 */
class SolutionCreationService {
    public function isSupportedTargetType(string $targetType): bool {
        return in_array($targetType, ['chasse', 'enigme'], true);
    }

    public function getCreationError(
        bool $supportedTargetType,
        bool $targetMatchesType,
        bool $isAuthenticated,
        bool $canCreate,
        bool $hasLinkedHunt,
        bool $alreadyExists
    ): ?string {
        if (!$supportedTargetType) {
            return 'type_invalide';
        }

        if (!$targetMatchesType) {
            return 'cible_invalide';
        }

        if (!$isAuthenticated) {
            return 'non_connecte';
        }

        if (!$canCreate || !$hasLinkedHunt) {
            return 'permission_refusee';
        }

        if ($alreadyExists) {
            return 'existe_deja';
        }

        return null;
    }

    /**
     * @return array{
     *     post_status:string,
     *     availability:string,
     *     delay_days:int,
     *     publication_time:string,
     *     system_state:string
     * }
     */
    public function getInitialState(string $disabledState): array {
        return [
            'post_status' => 'pending',
            'availability' => 'fin_chasse',
            'delay_days' => 0,
            'publication_time' => '00:00',
            'system_state' => $disabledState,
        ];
    }

    public function getGeneratedTitle(string $format, string $targetTitle): string {
        return sprintf($format, $targetTitle);
    }
}
