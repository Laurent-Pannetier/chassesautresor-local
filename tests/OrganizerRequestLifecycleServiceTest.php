<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Relationships\OrganizerRequestLifecycleService;
use PHPUnit\Framework\TestCase;

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}
if (!function_exists('current_time')) {
    function current_time(string $type)
    {
        return $type === 'mysql' ? gmdate('Y-m-d H:i:s') : time();
    }
}
if (!function_exists('get_user_meta')) {
    function get_user_meta($userId, $key, $single = false)
    {
        return $GLOBALS['organizer_request_meta'][$userId][$key] ?? '';
    }
}
if (!function_exists('update_user_meta')) {
    function update_user_meta($userId, $key, $value): void
    {
        $GLOBALS['organizer_request_meta'][$userId][$key] = $value;
    }
}
if (!function_exists('delete_user_meta')) {
    function delete_user_meta($userId, $key): void
    {
        unset($GLOBALS['organizer_request_meta'][$userId][$key]);
    }
}
if (!function_exists('wp_generate_password')) {
    function wp_generate_password(): string
    {
        return 'generated-token';
    }
}

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class OrganizerRequestLifecycleServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['organizer_request_meta'] = [];
    }

    public function testStartPersistsTokenAndSendsConfirmation(): void
    {
        $sent = [];
        $result = (new OrganizerRequestLifecycleService())->start(
            7,
            static function (int $userId, string $token) use (&$sent): bool {
                $sent = [$userId, $token];
                return true;
            }
        );

        self::assertTrue($result);
        self::assertSame([7, 'generated-token'], $sent);
        self::assertSame(
            'generated-token',
            $GLOBALS['organizer_request_meta'][7][OrganizerRequestLifecycleService::TOKEN_META]
        );
    }

    public function testConfirmClearsRequestCreatesOrganizerAndPromotesUser(): void
    {
        $GLOBALS['organizer_request_meta'][7] = [
            OrganizerRequestLifecycleService::TOKEN_META => 'valid-token',
            OrganizerRequestLifecycleService::DATE_META => current_time('mysql'),
        ];
        $promoted = [];

        $organizerId = (new OrganizerRequestLifecycleService())->confirm(
            7,
            'valid-token',
            static fn(int $userId): int => 42,
            static function (int $userId) use (&$promoted): void { $promoted[] = $userId; }
        );

        self::assertSame(42, $organizerId);
        self::assertSame([7], $promoted);
        self::assertSame([], $GLOBALS['organizer_request_meta'][7]);
    }
}
