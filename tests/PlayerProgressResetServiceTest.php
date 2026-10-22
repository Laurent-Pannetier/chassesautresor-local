<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\PlayerProgressResetService;
use PHPUnit\Framework\TestCase;

final class PlayerProgressResetServiceTest extends TestCase
{
    public function testResetClearsOnePlayerAndKeepsEngagement(): void
    {
        $database = $this->database();
        $winnerNames = [];
        $cleared = [];
        $cleaned = [];

        $result = (new PlayerProgressResetService($database, [
            'updateWinnerNames' => static function (int $huntId, string $names) use (&$winnerNames): void {
                $winnerNames[] = [$huntId, $names];
            },
            'clearCaches' => static function (array $huntIds, array $riddleIds) use (&$cleared): void {
                $cleared = [$huntIds, $riddleIds];
            },
            'cleanUserCache' => static function (int $userId) use (&$cleaned): void {
                $cleaned[] = $userId;
            },
        ]))->reset(7);

        self::assertSame('', $result['error']);
        self::assertGreaterThan(0, $result['deleted']);
        $sql = implode("\n", $database->queries);
        self::assertStringContainsString('DELETE FROM wp_enigme_statuts_utilisateur WHERE user_id = 7', $sql);
        self::assertStringContainsString('DELETE FROM wp_enigme_tentatives WHERE user_id = 7', $sql);
        self::assertStringContainsString('DELETE FROM wp_indices_deblocages WHERE user_id = 7', $sql);
        self::assertStringContainsString('DELETE FROM wp_enigme_etapes_progression WHERE user_id = 7', $sql);
        self::assertStringContainsString('DELETE FROM wp_enigme_delais_soumission WHERE user_id = 7', $sql);
        self::assertStringContainsString('DELETE FROM wp_chasse_winners WHERE user_id = 7', $sql);
        self::assertStringContainsString('DELETE FROM wp_notification_recipients WHERE user_id = 7', $sql);
        self::assertStringContainsString('DELETE FROM wp_user_messages WHERE user_id = 7', $sql);
        self::assertStringContainsString(
            "origin_type IN ('enigme', 'indice', 'tentative')",
            $sql
        );
        self::assertStringContainsString(
            "recipient_type = 'user' AND recipient_id = 7 AND event_key <> '"
            . PlayerProgressResetService::KEPT_TROPHY_EVENT_KEY . "'",
            $sql
        );
        self::assertStringContainsString("meta_key = '_myaccount_messages'", $sql);
        self::assertStringNotContainsString('DELETE FROM wp_engagements', $sql);
        self::assertStringNotContainsString('DELETE FROM wp_notifications ', $sql);
        self::assertStringContainsString('UPDATE wp_user_points SET balance = 100 WHERE id = 1', $sql);
        self::assertStringContainsString('UPDATE wp_user_points SET balance = 70 WHERE id = 2', $sql);
        self::assertSame([[12, 'Ada, bob']], $winnerNames);
        self::assertSame([[12], [3, 4, 5]], $cleared);
        self::assertSame([7], $cleaned);
    }

    public function testResetRejectsAnAnonymousUser(): void
    {
        $database = $this->database();

        $result = (new PlayerProgressResetService($database))->reset(0);

        self::assertSame(['deleted' => 0, 'error' => 'invalid_user'], $result);
        self::assertSame([], $database->queries);
    }

    private function database(): object
    {
        return new class {
            public string $prefix = 'wp_';
            public string $usermeta = 'wp_usermeta';
            public string $users = 'wp_users';
            public string $last_error = '';
            public int $rows_affected = 1;
            public array $queries = [];

            public function prepare(string $sql, ...$args): string
            {
                $index = 0;
                $replace = static function (array $match) use (&$index, $args): string {
                    $value = $args[$index++] ?? '';
                    if ($match[0] === '%d') {
                        return (string) (int) $value;
                    }

                    return "'" . (string) $value . "'";
                };

                return (string) preg_replace_callback('/%[ds]/', $replace, $sql);
            }

            public function query(string $sql): void
            {
                $this->queries[] = $sql;
            }

            public function get_col(string $sql): array
            {
                $this->queries[] = $sql;
                if (str_contains($sql, 'FROM wp_engagements')) {
                    return ['12'];
                }
                if (str_contains($sql, 'FROM wp_chasse_winners')) {
                    return ['12'];
                }
                if (str_contains($sql, 'FROM wp_enigme_statuts_utilisateur')) {
                    return ['3', '4'];
                }
                if (str_contains($sql, 'FROM wp_enigme_tentatives')) {
                    return ['4', '5'];
                }

                return [];
            }

            public function get_results(string $sql, $output = null): array
            {
                $this->queries[] = $sql;
                if (str_contains($sql, 'FROM wp_user_points')) {
                    return [
                        ['id' => 1, 'points' => 100],
                        ['id' => 2, 'points' => -30],
                    ];
                }
                if (str_contains($sql, 'JOIN wp_users')) {
                    return [
                        ['display_name' => 'Ada', 'user_login' => 'ada'],
                        ['display_name' => '', 'user_login' => 'bob'],
                    ];
                }

                return [];
            }
        };
    }
}
