<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Admin;

use Closure;

final class AdminStatisticsResetService
{
    private object $database;
    private Closure $huntCacheClearer;

    public function __construct(object $database, callable $huntCacheClearer)
    {
        $this->database = $database;
        $this->huntCacheClearer = Closure::fromCallable($huntCacheClearer);
    }

    /** @return array{deleted:int,error:string} */
    public function reset(): array
    {
        $tables = [
            $this->database->prefix . 'chasse_winners',
            $this->database->prefix . 'engagements',
            $this->database->prefix . 'enigme_statuts_utilisateur',
            $this->database->prefix . 'enigme_tentatives',
            $this->database->prefix . 'user_points',
            $this->database->prefix . 'indices_deblocages',
        ];
        $deleted = 0;

        foreach ($tables as $table) {
            $this->database->query("DELETE FROM {$table}");
            if (!empty($this->database->last_error)) {
                return ['deleted' => $deleted, 'error' => (string) $this->database->last_error];
            }
            $deleted += (int) $this->database->rows_affected;
        }

        delete_metadata('user', 0, '_myaccount_messages', '', true);
        if (!empty($this->database->last_error)) {
            return ['deleted' => $deleted, 'error' => (string) $this->database->last_error];
        }
        $deleted += (int) $this->database->rows_affected;

        $userIds = $this->database->get_col(
            "SELECT DISTINCT user_id FROM {$this->database->usermeta} "
            . "WHERE meta_key LIKE 'statut_enigme_%' "
            . "OR meta_key LIKE 'enigme_%_resolution_date' "
            . "OR meta_key LIKE 'indice_debloque_%' "
            . "OR meta_key LIKE 'souscription_chasse_%'"
        );

        foreach (
            ['statut_enigme_%', 'enigme_%_resolution_date', 'indice_debloque_%', 'souscription_chasse_%']
            as $pattern
        ) {
            $this->database->query("DELETE FROM {$this->database->usermeta} WHERE meta_key LIKE '{$pattern}'");
            if (!empty($this->database->last_error)) {
                return ['deleted' => $deleted, 'error' => (string) $this->database->last_error];
            }
            $deleted += (int) $this->database->rows_affected;
        }

        foreach ($userIds as $userId) {
            clean_user_cache((int) $userId);
        }

        $finishedHunts = get_posts([
            'post_type' => 'chasse',
            'post_status' => 'any',
            'meta_query' => [['key' => 'chasse_cache_statut', 'value' => 'termine']],
            'fields' => 'ids',
            'nopaging' => true,
        ]);
        foreach ($finishedHunts as $huntId) {
            update_field('chasse_cache_statut', 'en_cours', $huntId);
            delete_field('chasse_cache_gagnants', $huntId);
            delete_field('chasse_cache_date_decouverte', $huntId);
        }

        $allHunts = get_posts([
            'post_type' => 'chasse',
            'post_status' => 'any',
            'fields' => 'ids',
            'nopaging' => true,
        ]);
        foreach ($allHunts as $huntId) {
            ($this->huntCacheClearer)((int) $huntId);
        }

        return ['deleted' => $deleted, 'error' => ''];
    }
}
