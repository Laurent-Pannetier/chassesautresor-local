<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Persist completed intermediate steps for each player. */
class RiddleStepProgressRepository {
    private $wpdb;

    public function __construct($wpdb) {
        $this->wpdb = $wpdb;
    }

    /** @return int[] */
    public function findCompletedStepIds(int $userId, int $riddleId): array {
        if ($userId <= 0 || $riddleId <= 0) {
            return [];
        }

        $table = $this->wpdb->prefix . 'enigme_etapes_progression';
        $values = $this->wpdb->get_col(
            $this->wpdb->prepare(
                "SELECT etape_id FROM {$table} WHERE user_id = %d AND enigme_id = %d "
                    . "AND statut = 'trouvee' ORDER BY date_decouverte ASC, id ASC",
                $userId,
                $riddleId
            )
        );

        return array_values(array_unique(array_filter(array_map('intval', (array) $values))));
    }

    public function markCompleted(
        int $userId,
        int $riddleId,
        int $stepId,
        string $completedAt,
        ?string $attemptUid
    ): bool {
        if ($userId <= 0 || $riddleId <= 0 || $stepId <= 0 || $completedAt === '') {
            return false;
        }

        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'enigme_etapes_progression',
            [
                'user_id' => $userId,
                'enigme_id' => $riddleId,
                'etape_id' => $stepId,
                'statut' => 'trouvee',
                'date_decouverte' => $completedAt,
                'tentative_uid' => $attemptUid,
                'created_at' => $completedAt,
                'updated_at' => $completedAt,
            ],
            ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s']
        );

        return $result !== false;
    }

    public function deleteForRiddle(int $riddleId): int {
        if ($riddleId <= 0) {
            return 0;
        }

        $deleted = $this->wpdb->delete(
            $this->wpdb->prefix . 'enigme_etapes_progression',
            ['enigme_id' => $riddleId],
            ['%d']
        );

        return is_int($deleted) ? $deleted : 0;
    }

    public function deleteForStep(int $stepId): int {
        if ($stepId <= 0) {
            return 0;
        }

        $deleted = $this->wpdb->delete(
            $this->wpdb->prefix . 'enigme_etapes_progression',
            ['etape_id' => $stepId],
            ['%d']
        );

        return is_int($deleted) ? $deleted : 0;
    }
}
