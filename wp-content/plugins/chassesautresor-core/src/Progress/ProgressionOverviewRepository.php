<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Read the aggregates shown on the player and organizer progression pages. */
class ProgressionOverviewRepository
{
    private const STATUSES = [
        'non_commencee',
        'en_cours',
        'abandonnee',
        'echouee',
        'resolue',
        'terminee',
        'terminée',
        'soumis',
    ];

    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * Final attempts of one player, limited to the riddles of a hunt.
     *
     * @param int[] $riddleIds
     * @return array{total:int,success:int,pending:int}
     */
    public function summarizeUserAttempts(int $userId, array $riddleIds): array
    {
        $empty = ['total' => 0, 'success' => 0, 'pending' => 0];
        $riddleIds = $this->positiveIds($riddleIds);
        if ($userId <= 0 || $riddleIds === []) {
            return $empty;
        }

        $table = $this->wpdb->prefix . 'enigme_tentatives';
        [$inSql, $inParams] = $this->inClause('enigme_id', $riddleIds);
        $row = $this->row(
            "SELECT COUNT(*) AS total,"
            . " SUM(CASE WHEN resultat = 'bon' THEN 1 ELSE 0 END) AS success,"
            . " SUM(CASE WHEN resultat = 'attente' AND traitee = 0 THEN 1 ELSE 0 END) AS pending"
            . " FROM {$table} WHERE user_id = %d AND etape_id IS NULL AND {$inSql}",
            array_merge([$userId], $inParams)
        );

        return [
            'total' => (int) ($row['total'] ?? 0),
            'success' => (int) ($row['success'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
        ];
    }

    /**
     * @param int[] $riddleIds
     */
    public function countUnlockedHints(int $userId, int $huntId, array $riddleIds): int
    {
        $riddleIds = $this->positiveIds($riddleIds);
        if ($userId <= 0 || ($huntId <= 0 && $riddleIds === [])) {
            return 0;
        }

        $table = $this->wpdb->prefix . 'indices_deblocages';
        $where = 'user_id = %d AND (chasse_id = %d';
        $params = [$userId, max(0, $huntId)];
        if ($riddleIds !== []) {
            [$inSql, $inParams] = $this->inClause('enigme_id', $riddleIds);
            $where .= " OR {$inSql}";
            $params = array_merge($params, $inParams);
        }
        $where .= ')';

        return $this->var(
            "SELECT COUNT(DISTINCT indice_id) FROM {$table} WHERE {$where}",
            $params
        );
    }

    /**
     * @param int[] $riddleIds
     * @return array<int, array{attempts:int,last_at:string}>
     */
    public function userRiddleActivity(int $userId, array $riddleIds): array
    {
        $riddleIds = $this->positiveIds($riddleIds);
        if ($userId <= 0 || $riddleIds === []) {
            return [];
        }

        $table = $this->wpdb->prefix . 'enigme_tentatives';
        [$inSql, $inParams] = $this->inClause('enigme_id', $riddleIds);
        $rows = $this->rows(
            "SELECT enigme_id, COUNT(*) AS attempts, MAX(date_tentative) AS last_at"
            . " FROM {$table} WHERE user_id = %d AND etape_id IS NULL AND {$inSql}"
            . ' GROUP BY enigme_id',
            array_merge([$userId], $inParams)
        );

        $activity = [];
        foreach ($rows as $row) {
            $riddleId = (int) ($row['enigme_id'] ?? 0);
            if ($riddleId <= 0) {
                continue;
            }
            $activity[$riddleId] = [
                'attempts' => (int) ($row['attempts'] ?? 0),
                'last_at' => (string) ($row['last_at'] ?? ''),
            ];
        }

        return $activity;
    }

    /**
     * @param int[] $riddleIds
     * @return array<int, array{status:string,updated_at:string}>
     */
    public function userStatuses(int $userId, array $riddleIds): array
    {
        $riddleIds = $this->positiveIds($riddleIds);
        if ($userId <= 0 || $riddleIds === []) {
            return [];
        }

        $table = $this->wpdb->prefix . 'enigme_statuts_utilisateur';
        [$inSql, $inParams] = $this->inClause('enigme_id', $riddleIds);
        $rows = $this->rows(
            "SELECT enigme_id, statut, date_mise_a_jour FROM {$table}"
            . " WHERE user_id = %d AND {$inSql}",
            array_merge([$userId], $inParams)
        );

        $statuses = [];
        foreach ($rows as $row) {
            $riddleId = (int) ($row['enigme_id'] ?? 0);
            if ($riddleId <= 0) {
                continue;
            }
            $statuses[$riddleId] = [
                'status' => strtolower((string) ($row['statut'] ?? '')),
                'updated_at' => (string) ($row['date_mise_a_jour'] ?? ''),
            ];
        }

        return $statuses;
    }

    /**
     * @param int[] $riddleIds
     * @return array<int, array{count:int,last_at:string}>
     */
    public function userCompletedSteps(int $userId, array $riddleIds): array
    {
        $riddleIds = $this->positiveIds($riddleIds);
        if ($userId <= 0 || $riddleIds === []) {
            return [];
        }

        $table = $this->wpdb->prefix . 'enigme_etapes_progression';
        [$inSql, $inParams] = $this->inClause('enigme_id', $riddleIds);
        $rows = $this->rows(
            'SELECT enigme_id, COUNT(*) AS steps_done, MAX(date_decouverte) AS last_at'
            . " FROM {$table} WHERE user_id = %d AND statut = 'trouvee' AND {$inSql}"
            . ' GROUP BY enigme_id',
            array_merge([$userId], $inParams)
        );

        $steps = [];
        foreach ($rows as $row) {
            $riddleId = (int) ($row['enigme_id'] ?? 0);
            if ($riddleId <= 0) {
                continue;
            }
            $steps[$riddleId] = [
                'count' => (int) ($row['steps_done'] ?? 0),
                'last_at' => (string) ($row['last_at'] ?? ''),
            ];
        }

        return $steps;
    }

    /**
     * @param string[] $statuses
     * @param int[] $excludedUserIds
     */
    public function countPlayersWithStatus(int $riddleId, array $statuses, array $excludedUserIds = []): int
    {
        $statuses = $this->statuses($statuses);
        if ($riddleId <= 0 || $statuses === []) {
            return 0;
        }

        $table = $this->wpdb->prefix . 'enigme_statuts_utilisateur';
        $placeholders = implode(', ', array_fill(0, count($statuses), '%s'));
        [$excludeSql, $excludeParams] = $this->excludeClause('user_id', $excludedUserIds);

        return $this->var(
            "SELECT COUNT(DISTINCT user_id) FROM {$table}"
            . " WHERE enigme_id = %d AND statut IN ({$placeholders}){$excludeSql}",
            array_merge([$riddleId], $statuses, $excludeParams)
        );
    }

    /**
     * Final-answer breakdown. Intermediate steps stay out of this total.
     *
     * @param int[] $excludedUserIds
     * @return array{bon:int,variante:int,faux:int,attente:int,pending:int,total:int}
     */
    public function attemptBreakdown(
        int $riddleId,
        ?string $startAt = null,
        ?string $endAt = null,
        array $excludedUserIds = []
    ): array {
        $empty = ['bon' => 0, 'variante' => 0, 'faux' => 0, 'attente' => 0, 'pending' => 0, 'total' => 0];
        if ($riddleId <= 0) {
            return $empty;
        }

        $table = $this->wpdb->prefix . 'enigme_tentatives';
        $where = 'enigme_id = %d AND etape_id IS NULL';
        $params = [$riddleId];
        if ($startAt !== null && $endAt !== null) {
            $where .= ' AND date_tentative BETWEEN %s AND %s';
            $params[] = $startAt;
            $params[] = $endAt;
        }
        [$excludeSql, $excludeParams] = $this->excludeClause('user_id', $excludedUserIds);
        $row = $this->row(
            "SELECT COUNT(*) AS total,"
            . " SUM(CASE WHEN resultat = 'bon' THEN 1 ELSE 0 END) AS bon,"
            . " SUM(CASE WHEN resultat = 'variante' THEN 1 ELSE 0 END) AS variante,"
            . " SUM(CASE WHEN resultat = 'faux' THEN 1 ELSE 0 END) AS faux,"
            . " SUM(CASE WHEN resultat = 'attente' THEN 1 ELSE 0 END) AS attente,"
            . " SUM(CASE WHEN resultat = 'attente' AND traitee = 0 THEN 1 ELSE 0 END) AS pending"
            . " FROM {$table} WHERE {$where}{$excludeSql}",
            array_merge($params, $excludeParams)
        );

        return [
            'bon' => (int) ($row['bon'] ?? 0),
            'variante' => (int) ($row['variante'] ?? 0),
            'faux' => (int) ($row['faux'] ?? 0),
            'attente' => (int) ($row['attente'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'total' => (int) ($row['total'] ?? 0),
        ];
    }

    /**
     * @param int[] $excludedUserIds
     * @return array{indice_id:int,unlocks:int}|null
     */
    public function topHint(int $riddleId, array $excludedUserIds = []): ?array
    {
        if ($riddleId <= 0) {
            return null;
        }

        $table = $this->wpdb->prefix . 'indices_deblocages';
        [$excludeSql, $excludeParams] = $this->excludeClause('user_id', $excludedUserIds);
        $row = $this->row(
            "SELECT indice_id, COUNT(*) AS unlocks FROM {$table}"
            . " WHERE enigme_id = %d{$excludeSql}"
            . ' GROUP BY indice_id ORDER BY unlocks DESC, indice_id ASC LIMIT 1',
            array_merge([$riddleId], $excludeParams)
        );
        $hintId = (int) ($row['indice_id'] ?? 0);
        if ($hintId <= 0) {
            return null;
        }

        return [
            'indice_id' => $hintId,
            'unlocks' => (int) ($row['unlocks'] ?? 0),
        ];
    }

    /**
     * @param int[] $stepIds
     * @param int[] $excludedUserIds
     * @return array<int, int>
     */
    public function countPlayersByStep(array $stepIds, array $excludedUserIds = []): array
    {
        $stepIds = $this->positiveIds($stepIds);
        if ($stepIds === []) {
            return [];
        }

        $table = $this->wpdb->prefix . 'enigme_etapes_progression';
        [$inSql, $inParams] = $this->inClause('etape_id', $stepIds);
        [$excludeSql, $excludeParams] = $this->excludeClause('user_id', $excludedUserIds);
        $rows = $this->rows(
            "SELECT etape_id, COUNT(DISTINCT user_id) AS players FROM {$table}"
            . " WHERE statut = 'trouvee' AND {$inSql}{$excludeSql} GROUP BY etape_id",
            array_merge($inParams, $excludeParams)
        );

        return $this->mapCounts($rows, 'etape_id', 'players');
    }

    /**
     * @param int[] $stepIds
     * @param int[] $excludedUserIds
     * @return array<int, int>
     */
    public function countAttemptsByStep(
        array $stepIds,
        ?string $startAt = null,
        ?string $endAt = null,
        array $excludedUserIds = []
    ): array {
        $stepIds = $this->positiveIds($stepIds);
        if ($stepIds === []) {
            return [];
        }

        $table = $this->wpdb->prefix . 'enigme_tentatives';
        [$inSql, $inParams] = $this->inClause('etape_id', $stepIds);
        $where = $inSql;
        $params = $inParams;
        if ($startAt !== null && $endAt !== null) {
            $where .= ' AND date_tentative BETWEEN %s AND %s';
            $params[] = $startAt;
            $params[] = $endAt;
        }
        [$excludeSql, $excludeParams] = $this->excludeClause('user_id', $excludedUserIds);
        $rows = $this->rows(
            "SELECT etape_id, COUNT(*) AS attempts FROM {$table}"
            . " WHERE {$where}{$excludeSql} GROUP BY etape_id",
            array_merge($params, $excludeParams)
        );

        return $this->mapCounts($rows, 'etape_id', 'attempts');
    }

    public function countWinners(int $huntId): int
    {
        if ($huntId <= 0) {
            return 0;
        }

        $table = $this->wpdb->prefix . 'chasse_winners';

        return $this->var("SELECT COUNT(*) FROM {$table} WHERE chasse_id = %d", [$huntId]);
    }

    /**
     * @param int[] $ids
     * @return int[]
     */
    private function positiveIds(array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0
        )));
    }

    /**
     * @param string[] $statuses
     * @return string[]
     */
    private function statuses(array $statuses): array
    {
        $normalized = array_map(
            static fn ($status): string => strtolower(trim((string) $status)),
            $statuses
        );

        return array_values(array_unique(array_filter(
            $normalized,
            static fn (string $status): bool => in_array($status, self::STATUSES, true)
        )));
    }

    /**
     * @param int[] $ids
     * @return array{0:string,1:int[]}
     */
    private function inClause(string $column, array $ids): array
    {
        $placeholders = implode(', ', array_fill(0, count($ids), '%d'));

        return ["{$column} IN ({$placeholders})", $ids];
    }

    /**
     * @param int[] $excludedUserIds
     * @return array{0:string,1:int[]}
     */
    private function excludeClause(string $column, array $excludedUserIds): array
    {
        $excludedUserIds = $this->positiveIds($excludedUserIds);
        if ($excludedUserIds === []) {
            return ['', []];
        }

        $placeholders = implode(', ', array_fill(0, count($excludedUserIds), '%d'));

        return [" AND {$column} NOT IN ({$placeholders})", $excludedUserIds];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, int>
     */
    private function mapCounts(array $rows, string $idKey, string $countKey): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $id = (int) ($row[$idKey] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $counts[$id] = (int) ($row[$countKey] ?? 0);
        }

        return $counts;
    }

    /** @param array<int, int|string> $params */
    private function var(string $sql, array $params): int
    {
        return (int) $this->wpdb->get_var($this->wpdb->prepare($sql, ...$params));
    }

    /**
     * @param array<int, int|string> $params
     * @return array<string, mixed>
     */
    private function row(string $sql, array $params): array
    {
        $row = $this->wpdb->get_row($this->wpdb->prepare($sql, ...$params), ARRAY_A);

        return is_array($row) ? $row : [];
    }

    /**
     * @param array<int, int|string> $params
     * @return array<int, array<string, mixed>>
     */
    private function rows(string $sql, array $params): array
    {
        $rows = $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params), ARRAY_A);

        return is_array($rows) ? $rows : [];
    }
}
