<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Query completion state for hunt riddles.
 */
class HuntProgressRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /** @param int[] $riddleIds */
    public function countSolved(int $userId, array $riddleIds): int
    {
        if ($riddleIds === []) {
            return 0;
        }

        $table = $this->wpdb->prefix . 'enigme_statuts_utilisateur';
        $placeholders = implode(',', array_fill(0, count($riddleIds), '%d'));
        $sql = "SELECT COUNT(DISTINCT enigme_id) FROM {$table} "
            . "WHERE user_id = %d AND statut IN ('resolue','terminee','terminée') "
            . "AND enigme_id IN ({$placeholders})";

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare($sql, array_merge([$userId], $riddleIds))
        );
    }

    /** @param int[] $riddleIds */
    public function countEngaged(int $userId, array $riddleIds): int
    {
        if ($riddleIds === []) {
            return 0;
        }

        $table = $this->wpdb->prefix . 'engagements';
        $placeholders = implode(',', array_fill(0, count($riddleIds), '%d'));
        $sql = "SELECT COUNT(DISTINCT enigme_id) FROM {$table} "
            . "WHERE user_id = %d AND enigme_id IN ({$placeholders})";

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare($sql, array_merge([$userId], $riddleIds))
        );
    }

    /** @param int[] $riddleIds */
    public function countValidatable(array $riddleIds): int
    {
        if ($riddleIds === []) {
            return 0;
        }

        $table = $this->wpdb->prefix . 'postmeta';
        $placeholders = implode(',', array_fill(0, count($riddleIds), '%d'));
        $sql = "SELECT COUNT(DISTINCT post_id) FROM {$table} WHERE meta_key = %s "
            . "AND meta_value <> %s AND post_id IN ({$placeholders})";

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                $sql,
                array_merge(['enigme_mode_validation', 'aucune'], $riddleIds)
            )
        );
    }

    /**
     * Return users who completed all required riddles, ordered by first completion.
     *
     * @param int[] $validatable
     * @param int[] $engagementOnly
     * @return object[] Objects exposing user_id and first_finish.
     */
    public function findCompletedUsers(array $validatable, array $engagementOnly): array
    {
        if ($validatable !== []) {
            $results = $this->findUsersWhoSolvedAll($validatable);

            if ($engagementOnly === []) {
                return $results;
            }

            return array_values(
                array_filter(
                    $results,
                    fn ($row): bool => $this->countEngaged((int) $row->user_id, $engagementOnly)
                        === count($engagementOnly)
                )
            );
        }

        if ($engagementOnly !== []) {
            return $this->findUsersWhoEngagedAll($engagementOnly);
        }

        return [];
    }

    /**
     * Mark every stored user status for the supplied riddles as completed.
     *
     * @param int[] $riddleIds
     * @return array<int, int[]> User IDs indexed by riddle ID.
     */
    public function completeRiddles(array $riddleIds, string $completedAt): array
    {
        $table = $this->wpdb->prefix . 'enigme_statuts_utilisateur';
        $usersByRiddle = [];

        foreach ($riddleIds as $riddleId) {
            $riddleId = (int) $riddleId;
            $this->wpdb->update(
                $table,
                [
                    'statut' => 'terminee',
                    'date_mise_a_jour' => $completedAt,
                ],
                ['enigme_id' => $riddleId],
                ['%s', '%s'],
                ['%d']
            );

            $usersByRiddle[$riddleId] = array_map(
                'intval',
                $this->wpdb->get_col(
                    $this->wpdb->prepare(
                        "SELECT DISTINCT user_id FROM {$table} WHERE enigme_id = %d",
                        $riddleId
                    )
                )
            );
        }

        return $usersByRiddle;
    }

    /**
     * @param int[] $riddleIds
     * @return object[]
     */
    private function findUsersWhoSolvedAll(array $riddleIds): array
    {
        $table = $this->wpdb->prefix . 'enigme_statuts_utilisateur';
        $placeholders = implode(',', array_fill(0, count($riddleIds), '%d'));
        $sql = "SELECT user_id, MIN(date_mise_a_jour) AS first_finish FROM {$table} "
            . "WHERE statut IN ('resolue','terminee','terminée') AND enigme_id IN ({$placeholders}) "
            . 'GROUP BY user_id HAVING COUNT(DISTINCT enigme_id) = %d ORDER BY first_finish ASC';

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, array_merge($riddleIds, [count($riddleIds)]))
        );
    }

    /**
     * @param int[] $riddleIds
     * @return object[]
     */
    private function findUsersWhoEngagedAll(array $riddleIds): array
    {
        $table = $this->wpdb->prefix . 'engagements';
        $placeholders = implode(',', array_fill(0, count($riddleIds), '%d'));
        $sql = "SELECT user_id, MIN(date_engagement) AS first_finish FROM {$table} "
            . "WHERE enigme_id IN ({$placeholders}) GROUP BY user_id "
            . 'HAVING COUNT(DISTINCT enigme_id) = %d ORDER BY first_finish ASC';

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, array_merge($riddleIds, [count($riddleIds)]))
        );
    }
}
