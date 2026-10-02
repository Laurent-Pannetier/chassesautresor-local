<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Admin;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Admin/AdminStatisticsResetService.php';

function delete_metadata(...$arguments): bool
{
    $GLOBALS['admin_reset_deleted_metadata'] = $arguments;
    return true;
}

function clean_user_cache(int $userId): void
{
    $GLOBALS['admin_reset_cleaned_users'][] = $userId;
}

function get_posts(array $arguments): array
{
    $GLOBALS['admin_reset_post_queries'][] = $arguments;
    return isset($arguments['meta_query']) ? [10] : [10, 20];
}

function update_field(string $field, string $value, int $postId): void
{
    $GLOBALS['admin_reset_updated_fields'][] = [$field, $value, $postId];
}

function delete_field(string $field, int $postId): void
{
    $GLOBALS['admin_reset_deleted_fields'][] = [$field, $postId];
}

function wp_cache_delete(string $key, string $group): void
{
    $GLOBALS['admin_reset_object_cache'][] = [$key, $group];
}

function delete_transient(string $key): void
{
    $GLOBALS['admin_reset_transients'][] = $key;
}

final class AdminStatisticsResetServiceTest extends TestCase
{
    public function testItClearsProgressDataAndHuntCaches(): void
    {
        $database = new class {
            public string $prefix = 'wp_';
            public string $usermeta = 'wp_usermeta';
            public string $last_error = '';
            public int $rows_affected = 1;
            public array $queries = [];

            public function query(string $sql): void
            {
                $this->queries[] = $sql;
            }

            public function get_col(string $sql): array
            {
                $this->queries[] = $sql;
                return [4, 7];
            }
        };
        $clearedHunts = [];
        $GLOBALS['admin_reset_cleaned_users'] = [];
        $GLOBALS['admin_reset_post_queries'] = [];
        $GLOBALS['admin_reset_updated_fields'] = [];
        $GLOBALS['admin_reset_deleted_fields'] = [];

        $result = (new AdminStatisticsResetService(
            $database,
            static function (int $huntId) use (&$clearedHunts): void {
                $clearedHunts[] = $huntId;
            }
        ))->reset();

        self::assertSame(['deleted' => 12, 'error' => ''], $result);
        self::assertContains('DELETE FROM wp_enigme_etapes_progression', $database->queries);
        self::assertSame(['user', 0, '_myaccount_messages', '', true], $GLOBALS['admin_reset_deleted_metadata']);
        self::assertSame([4, 7], $GLOBALS['admin_reset_cleaned_users']);
        self::assertSame([10, 20], $clearedHunts);
        self::assertContains(['chasse_cache_statut', 'en_cours', 10], $GLOBALS['admin_reset_updated_fields']);
        self::assertContains(['chasse_cache_gagnants', 10], $GLOBALS['admin_reset_deleted_fields']);
    }

    public function testItStopsAtTheFirstDatabaseError(): void
    {
        $database = new class {
            public string $prefix = 'wp_';
            public string $usermeta = 'wp_usermeta';
            public string $last_error = '';
            public int $rows_affected = 0;

            public function query(string $sql): void
            {
                $this->last_error = 'database error';
            }

            public function get_col(string $sql): array
            {
                return [];
            }
        };

        $result = (new AdminStatisticsResetService($database, static function (): void {
        }))->reset();

        self::assertSame(['deleted' => 0, 'error' => 'database error'], $result);
    }

    public function testItCanClearHuntDisplayCacheWithoutAThemeCallback(): void
    {
        $GLOBALS['admin_reset_object_cache'] = [];
        $GLOBALS['admin_reset_transients'] = [];

        AdminStatisticsResetService::clearHuntDisplayCache(42);

        self::assertSame(
            [['chasse_infos_affichage_v2_42', 'chasse_affichage']],
            $GLOBALS['admin_reset_object_cache']
        );
        self::assertSame(['chasse_infos_affichage_v2_42'], $GLOBALS['admin_reset_transients']);
    }
}
