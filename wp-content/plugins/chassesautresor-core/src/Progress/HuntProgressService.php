<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Calculate hunt progress from validatable and engagement-only riddles.
 */
class HuntProgressService
{
    private HuntProgressRepository $repository;

    public function __construct(HuntProgressRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param int[] $validatable
     * @param int[] $engagementOnly
     * @return array{completed:int,total:int,is_complete:bool}
     */
    public function calculate(int $userId, array $validatable, array $engagementOnly): array
    {
        if ($userId <= 0) {
            return ['completed' => 0, 'total' => 0, 'is_complete' => false];
        }

        $solved = $this->repository->countSolved($userId, $validatable);
        $engaged = $this->repository->countEngaged($userId, $engagementOnly);
        $total = count($validatable) + count($engagementOnly);
        $completed = $solved + $engaged;

        return [
            'completed' => $completed,
            'total' => $total,
            'is_complete' => $total > 0 && $completed === $total,
        ];
    }

    public function getRiddleStatus(int $userId, int $riddleId): ?string
    {
        if ($userId <= 0 || $riddleId <= 0) {
            return null;
        }

        return $this->repository->findStatus($userId, $riddleId);
    }

    public function getRiddleResolutionDate(int $userId, int $riddleId): ?string
    {
        if ($userId <= 0 || $riddleId <= 0) {
            return null;
        }

        return $this->repository->findResolutionDate($userId, $riddleId);
    }

    /** @param int[] $prerequisiteIds */
    public function areRiddlePrerequisitesMet(
        int $userId,
        array $prerequisiteIds,
        string $accessCondition = 'immediat'
    ): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $prerequisiteIds = array_values(array_filter(
            array_map('intval', $prerequisiteIds),
            static function (int $riddleId): bool {
                return $riddleId > 0;
            }
        ));
        if ($prerequisiteIds === []) {
            return $accessCondition !== 'pre_requis';
        }

        foreach ($prerequisiteIds as $riddleId) {
            $status = $this->repository->findStatus($userId, $riddleId);
            if ($status === null || !in_array($this->normalizeStatus($status), ['resolue', 'terminee'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{
     *     est_verrouillee:bool,
     *     motif:string,
     *     date_deblocage:?string,
     *     timestamp_restant:?int,
     *     cout_points:?int,
     *     message_variante:?string
     * }
     */
    public function getRiddleLockState(
        int $userId,
        string $status,
        ?int $unlockTimestamp = null,
        ?string $formattedUnlockDate = null,
        ?int $currentTimestamp = null
    ): array {
        $result = [
            'est_verrouillee' => false,
            'motif' => 'aucun',
            'date_deblocage' => null,
            'timestamp_restant' => null,
            'cout_points' => null,
            'message_variante' => null,
        ];

        if ($userId <= 0) {
            $result['est_verrouillee'] = true;
            $result['motif'] = 'utilisateur_non_connecte';
            return $result;
        }

        $status = $this->normalizeStatus($status);
        $reasons = [
            'bloquee_pre_requis' => 'pre_requis',
            'bloquee_chasse' => 'chasse_indisponible',
            'non_souscrite' => 'non_souscrit',
            'invalide' => 'erreur_configuration',
        ];

        if ($status === 'bloquee_date') {
            $currentTimestamp = $currentTimestamp ?? time();
            $result['est_verrouillee'] = true;
            if ($unlockTimestamp !== null && $unlockTimestamp > $currentTimestamp) {
                $result['motif'] = 'date_future';
                $result['date_deblocage'] = $formattedUnlockDate;
                $result['timestamp_restant'] = $unlockTimestamp - $currentTimestamp;
                return $result;
            }

            $result['motif'] = 'date_non_definie';
            return $result;
        }

        if (isset($reasons[$status])) {
            $result['est_verrouillee'] = true;
            $result['motif'] = $reasons[$status];
        }

        return $result;
    }

    /**
     * @return array{
     *     etat:string,
     *     rediriger:bool,
     *     afficher_formulaire:bool,
     *     afficher_message:bool,
     *     message_html:string
     * }
     */
    public function getRiddleParticipationState(string $status): array
    {
        $status = $this->normalizeStatus($status);
        $redirectStatuses = [
            'abandonnee',
            'bloquee_date',
            'bloquee_chasse',
            'bloquee_pre_requis',
            'invalide',
            'cache_invalide',
        ];

        return [
            'etat' => $status,
            'rediriger' => in_array($status, $redirectStatuses, true),
            'afficher_formulaire' => in_array($status, ['en_cours', 'non_souscrite', 'echouee'], true),
            'afficher_message' => false,
            'message_html' => '',
        ];
    }

    public function calculateRiddleSystemState(
        bool $hasValidHunt,
        string $huntStatus,
        string $accessCondition,
        ?int $scheduledTimestamp,
        string $validationMode,
        bool $hasAnswers,
        ?int $currentTimestamp = null
    ): string {
        if (!$hasValidHunt || !in_array($huntStatus, ['en_cours', 'payante', 'termine'], true)) {
            return 'bloquee_chasse';
        }

        if ($accessCondition === 'date_programmee') {
            $currentTimestamp = $currentTimestamp ?? time();
            if ($scheduledTimestamp === null || $scheduledTimestamp > $currentTimestamp) {
                return 'bloquee_date';
            }
        } elseif ($accessCondition === 'pre_requis') {
            return 'bloquee_pre_requis';
        }

        if ($validationMode === 'automatique' && !$hasAnswers) {
            return 'invalide';
        }

        return 'accessible';
    }

    public function deleteRiddleStatuses(int $riddleId): int
    {
        return $riddleId > 0 ? $this->repository->deleteStatusesForRiddle($riddleId) : 0;
    }

    public function advanceRiddleStatus(
        int $userId,
        int $riddleId,
        string $newStatus,
        string $updatedAt,
        bool $force = false
    ): bool {
        $newStatus = $this->normalizeStatus($newStatus);
        $priorities = [
            'non_commencee' => 0,
            'soumis' => 1,
            'en_cours' => 2,
            'abandonnee' => 3,
            'echouee' => 4,
            'resolue' => 5,
            'terminee' => 6,
        ];
        if ($userId <= 0 || $riddleId <= 0 || !isset($priorities[$newStatus])) {
            return false;
        }

        $storedStatus = $this->repository->findStatus($userId, $riddleId);
        $currentStatus = $storedStatus !== null ? $this->normalizeStatus($storedStatus) : null;
        if (!$force && in_array($currentStatus, ['resolue', 'terminee'], true)) {
            return false;
        }
        if (!$force && $priorities[$newStatus] <= ($priorities[$currentStatus] ?? 0)) {
            return false;
        }

        $this->repository->persistStatus($userId, $riddleId, $newStatus, $updatedAt, $storedStatus !== null);
        return true;
    }

    private function normalizeStatus(string $status): string
    {
        return strtolower(strtr(trim($status), [
            'à' => 'a',
            'â' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'î' => 'i',
            'ï' => 'i',
            'ô' => 'o',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
        ]));
    }

    /**
     * @param int[] $validatable
     * @param int[] $engagementOnly
     * @return object[]
     */
    public function getCompletedUsers(array $validatable, array $engagementOnly): array
    {
        return $this->repository->findCompletedUsers($validatable, $engagementOnly);
    }

    /** @param int[] $riddleIds */
    public function countSolvedRiddles(int $userId, array $riddleIds): int
    {
        if ($userId <= 0) {
            return 0;
        }

        return $this->repository->countSolved($userId, $riddleIds);
    }

    /** @param int[] $riddleIds */
    public function countEngagedRiddles(int $userId, array $riddleIds): int
    {
        if ($userId <= 0) {
            return 0;
        }

        return $this->repository->countEngaged($userId, $riddleIds);
    }

    /** @param int[] $riddleIds */
    public function countValidatableRiddles(array $riddleIds): int
    {
        return $this->repository->countValidatable($riddleIds);
    }

    /**
     * @param int[] $riddleIds
     * @return array<int, int[]> User IDs indexed by riddle ID.
     */
    public function completeRiddles(array $riddleIds, string $completedAt): array
    {
        return $this->repository->completeRiddles($riddleIds, $completedAt);
    }
}
