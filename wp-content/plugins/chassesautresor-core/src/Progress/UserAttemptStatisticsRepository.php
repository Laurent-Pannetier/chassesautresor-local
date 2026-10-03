<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Query attempt totals for a user account.
 */
class UserAttemptStatisticsRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /** @return array{pending: int, total: int, success: int} */
    public function summarize(int $userId): array
    {
        $table = $this->wpdb->prefix . 'enigme_tentatives';
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT COUNT(*) AS total,"
                . " SUM(CASE WHEN resultat = 'attente' AND traitee = 0 THEN 1 ELSE 0 END) AS pending,"
                . " SUM(CASE WHEN resultat = 'bon' THEN 1 ELSE 0 END) AS success"
                . " FROM {$table} WHERE user_id = %d AND etape_id IS NULL",
                $userId
            ),
            ARRAY_A
        );

        return [
            'pending' => (int) ($row['pending'] ?? 0),
            'total' => (int) ($row['total'] ?? 0),
            'success' => (int) ($row['success'] ?? 0),
        ];
    }

    public function countForUser(int $userId, string $search = ''): int
    {
        [$from, $where, $params] = $this->buildUserQuery($userId, $search);

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*){$from}{$where}", ...$params)
        );
    }

    /** @return object[] */
    public function findForUser(int $userId, string $search, int $limit, int $offset): array
    {
        [$from, $where, $params] = $this->buildUserQuery($userId, $search);
        $sql = 'SELECT t.*, p.post_title AS enigme_title, '
            . "COALESCE(chasse_meta.chasse_id, 0) AS chasse_id, chasses.post_title AS chasse_title{$from}{$where} "
            . 'ORDER BY t.date_tentative DESC LIMIT %d OFFSET %d';
        $params[] = $limit;
        $params[] = $offset;

        return (array) $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params));
    }

    /** @return array{string, string, array<int, int|string>} */
    private function buildUserQuery(int $userId, string $search): array
    {
        $attempts = $this->wpdb->prefix . 'enigme_tentatives';
        $from = " FROM {$attempts} t INNER JOIN {$this->wpdb->posts} p ON t.enigme_id = p.ID"
            . ' LEFT JOIN (SELECT pm.post_id, MAX(CAST(SUBSTRING_INDEX('
            . "SUBSTRING_INDEX(pm.meta_value, ';', 2), ':', -1) AS UNSIGNED)) AS chasse_id"
            . " FROM {$this->wpdb->postmeta} pm"
            . " WHERE pm.meta_key IN ('chasse_associee', 'enigme_chasse_associee')"
            . ' GROUP BY pm.post_id) AS chasse_meta ON chasse_meta.post_id = t.enigme_id'
            . " LEFT JOIN {$this->wpdb->posts} chasses ON chasses.ID = chasse_meta.chasse_id";
        $where = ' WHERE t.user_id = %d AND t.etape_id IS NULL';
        $params = [$userId];

        if ($search !== '') {
            $like = '%' . $this->wpdb->esc_like($search) . '%';
            $where .= ' AND (p.post_title LIKE %s OR t.reponse_saisie LIKE %s OR chasses.post_title LIKE %s)';
            array_push($params, $like, $like, $like);
        }

        return [$from, $where, $params];
    }
}
