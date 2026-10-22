<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Admin\AdminStatisticsResetService;

/**
 * Erase one player's progression and keep the engagements that let them continue.
 */
final class PlayerProgressResetService
{
    /**
     * Welcome trophy stored by cat_dispatch_trophy_event('bienvenue', ...).
     */
    public const KEPT_TROPHY_EVENT_KEY = 'code_event:bienvenue';

    /** @var string[] */
    private const PROGRESS_POINT_ORIGINS = ['enigme', 'indice', 'tentative'];

    /** @var string[] */
    private const PROGRESS_TABLES = [
        'enigme_statuts_utilisateur',
        'enigme_tentatives',
        'indices_deblocages',
        'enigme_etapes_progression',
        'enigme_delais_soumission',
        'notification_recipients',
        'user_messages',
    ];

    private object $database;

    /** @var callable(int, string): void */
    private $updateWinnerNames;

    /** @var callable(int[], int[]): void */
    private $clearCaches;

    /** @var callable(int): void */
    private $cleanUserCache;

    private int $deleted = 0;

    /**
     * @param array{
     *     updateWinnerNames?: callable(int, string): void,
     *     clearCaches?: callable(int[], int[]): void,
     *     cleanUserCache?: callable(int): void
     * } $hooks
     */
    public function __construct(object $database, array $hooks = [])
    {
        $this->database = $database;
        $this->updateWinnerNames = $hooks['updateWinnerNames'] ?? [$this, 'storeWinnerNames'];
        $this->clearCaches = $hooks['clearCaches'] ?? [$this, 'clearProgressCaches'];
        $this->cleanUserCache = $hooks['cleanUserCache'] ?? [$this, 'forgetUser'];
    }

    /** @return array{deleted:int,error:string} */
    public function reset(int $userId): array
    {
        if ($userId <= 0) {
            return ['deleted' => 0, 'error' => 'invalid_user'];
        }

        $this->deleted = 0;
        $huntIds = $this->intColumn(
            "SELECT DISTINCT chasse_id FROM {$this->table('engagements')}"
            . ' WHERE user_id = %d AND chasse_id IS NOT NULL',
            [$userId]
        );
        if ($this->failed()) {
            return $this->failure();
        }

        $wonHuntIds = $this->intColumn(
            "SELECT DISTINCT chasse_id FROM {$this->table('chasse_winners')} WHERE user_id = %d",
            [$userId]
        );
        if ($this->failed()) {
            return $this->failure();
        }

        $riddleIds = array_values(array_unique(array_merge(
            $this->intColumn(
                "SELECT DISTINCT enigme_id FROM {$this->table('enigme_statuts_utilisateur')}"
                . ' WHERE user_id = %d',
                [$userId]
            ),
            $this->intColumn(
                "SELECT DISTINCT enigme_id FROM {$this->table('enigme_tentatives')} WHERE user_id = %d",
                [$userId]
            )
        )));
        if ($this->failed()) {
            return $this->failure();
        }

        foreach (self::PROGRESS_TABLES as $table) {
            if ($this->execute(
                "DELETE FROM {$this->table($table)} WHERE user_id = %d",
                [$userId]
            ) !== null) {
                return $this->failure();
            }
        }

        if ($this->execute(
            "DELETE FROM {$this->table('chasse_winners')} WHERE user_id = %d",
            [$userId]
        ) !== null) {
            return $this->failure();
        }

        $origins = implode(', ', array_fill(0, count(self::PROGRESS_POINT_ORIGINS), '%s'));
        if ($this->execute(
            "DELETE FROM {$this->table('user_points')} WHERE user_id = %d AND origin_type IN ({$origins})",
            array_merge([$userId], self::PROGRESS_POINT_ORIGINS)
        ) !== null) {
            return $this->failure();
        }

        if ($this->rewritePointBalances($userId) !== null) {
            return $this->failure();
        }

        if ($this->execute(
            "DELETE FROM {$this->table('trophy_attributions')}"
            . " WHERE recipient_type = 'user' AND recipient_id = %d AND event_key <> %s",
            [$userId, self::KEPT_TROPHY_EVENT_KEY]
        ) !== null) {
            return $this->failure();
        }

        $usermeta = (string) $this->database->usermeta;
        if ($this->execute(
            "DELETE FROM {$usermeta} WHERE user_id = %d AND ("
            . 'meta_key LIKE %s OR meta_key LIKE %s OR meta_key LIKE %s'
            . ' OR meta_key LIKE %s OR meta_key = %s)',
            [
                $userId,
                'statut_enigme_%',
                'enigme_%_resolution_date',
                'indice_debloque_%',
                'souscription_chasse_%',
                '_myaccount_messages',
            ]
        ) !== null) {
            return $this->failure();
        }

        foreach ($wonHuntIds as $huntId) {
            ($this->updateWinnerNames)($huntId, $this->winnerNames($huntId));
            if ($this->failed()) {
                return $this->failure();
            }
        }

        $cacheHuntIds = array_values(array_unique(array_merge($huntIds, $wonHuntIds)));
        ($this->clearCaches)($cacheHuntIds, $riddleIds);
        ($this->cleanUserCache)($userId);

        return ['deleted' => $this->deleted, 'error' => ''];
    }

    /** @param array<int, int|string> $params */
    private function rewritePointBalances(int $userId): ?string
    {
        $rows = $this->database->get_results(
            $this->database->prepare(
                "SELECT id, points FROM {$this->table('user_points')} WHERE user_id = %d ORDER BY id ASC",
                $userId
            ),
            defined('ARRAY_A') ? ARRAY_A : 'ARRAY_A'
        );
        if ($this->failed()) {
            return $this->database->last_error;
        }

        $balance = 0;
        foreach (is_array($rows) ? $rows : [] as $row) {
            $balance = max(0, $balance + (int) $this->value($row, 'points'));
            $error = $this->execute(
                "UPDATE {$this->table('user_points')} SET balance = %d WHERE id = %d",
                [$balance, (int) $this->value($row, 'id')]
            );
            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }

    private function winnerNames(int $huntId): string
    {
        $users = isset($this->database->users)
            ? (string) $this->database->users
            : $this->database->prefix . 'users';
        $rows = $this->database->get_results(
            $this->database->prepare(
                "SELECT u.display_name, u.user_login FROM {$this->table('chasse_winners')} w"
                . " INNER JOIN {$users} u ON u.ID = w.user_id"
                . ' WHERE w.chasse_id = %d ORDER BY w.date_win ASC, w.id ASC',
                $huntId
            ),
            defined('ARRAY_A') ? ARRAY_A : 'ARRAY_A'
        );
        $names = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $name = trim((string) $this->value($row, 'display_name'));
            if ($name === '') {
                $name = trim((string) $this->value($row, 'user_login'));
            }
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return implode(', ', $names);
    }

    /** @param array<int, int|string> $params */
    private function execute(string $sql, array $params): ?string
    {
        $this->database->query($this->database->prepare($sql, ...$params));
        if ($this->failed()) {
            return (string) $this->database->last_error;
        }
        $this->deleted += (int) $this->database->rows_affected;

        return null;
    }

    /**
     * @param array<int, int|string> $params
     * @return int[]
     */
    private function intColumn(string $sql, array $params): array
    {
        $values = $this->database->get_col($this->database->prepare($sql, ...$params));
        $ids = [];
        foreach (is_array($values) ? $values : [] as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    private function table(string $name): string
    {
        return $this->database->prefix . $name;
    }

    private function failed(): bool
    {
        return !empty($this->database->last_error);
    }

    /** @return array{deleted:int,error:string} */
    private function failure(): array
    {
        return ['deleted' => $this->deleted, 'error' => (string) $this->database->last_error];
    }

    /** @param array<string, mixed>|object $row */
    private function value($row, string $key)
    {
        if (is_array($row)) {
            return $row[$key] ?? null;
        }

        return is_object($row) ? ($row->{$key} ?? null) : null;
    }

    private function storeWinnerNames(int $huntId, string $names): void
    {
        if (function_exists('update_field')) {
            update_field('chasse_cache_gagnants', $names, $huntId);
        }
    }

    /**
     * @param int[] $huntIds
     * @param int[] $riddleIds
     */
    private function clearProgressCaches(array $huntIds, array $riddleIds): void
    {
        $statistics = class_exists(StatisticsCacheService::class) ? new StatisticsCacheService() : null;
        foreach ($huntIds as $huntId) {
            if ($statistics !== null) {
                $statistics->clear('chasse', $huntId);
            }
            AdminStatisticsResetService::clearHuntDisplayCache($huntId);
        }
        foreach ($riddleIds as $riddleId) {
            if ($statistics !== null) {
                $statistics->clear('enigme', $riddleId);
            }
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('enigme_sidebar_resolution_' . $riddleId, 'chassesautresor');
            }
        }
    }

    private function forgetUser(int $userId): void
    {
        if (function_exists('clean_user_cache')) {
            clean_user_cache($userId);
        }
    }
}
