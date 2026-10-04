<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Site\SingleHuntPublicAccessHandler;
use PHPUnit\Framework\TestCase;

final class SingleHuntPublicAccessHandlerTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRedirectsOrganizerSingularPagesForNonAdminsInSingleHuntMode(): void
    {
        function current_user_can($cap): bool
        {
            return false;
        }
        function get_query_var($key)
        {
            return '';
        }
        function cat_are_organizer_applications_open(): bool
        {
            return false;
        }
        function is_page($slugs): bool
        {
            return false;
        }
        function cat_is_single_hunt_mode(): bool
        {
            return true;
        }
        function is_singular($type): bool
        {
            return $type === 'organisateur';
        }
        function home_url($path = '/'): string
        {
            return 'https://example.com' . $path;
        }
        function wp_safe_redirect($url, $status = 302, $xRedirectBy = ''): void
        {
            $GLOBALS['cat_test_redirect'] = [$url, $status];
            throw new RuntimeException('redirected');
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Site/SingleHuntPublicAccessHandler.php';

        try {
            SingleHuntPublicAccessHandler::redirectClosedEntrances();
            self::fail('Expected redirect');
        } catch (RuntimeException $exception) {
            self::assertSame('redirected', $exception->getMessage());
        }

        self::assertSame(['https://example.com/', 302], $GLOBALS['cat_test_redirect']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAdminsCanStillOpenOrganizerPagesInSingleHuntMode(): void
    {
        $GLOBALS['cat_test_admin_redirected'] = false;

        function current_user_can($cap): bool
        {
            return $cap === 'manage_options';
        }
        function get_query_var($key)
        {
            return '';
        }
        function cat_are_organizer_applications_open(): bool
        {
            return true;
        }
        function is_page($slugs): bool
        {
            return false;
        }
        function cat_is_single_hunt_mode(): bool
        {
            return true;
        }
        function is_singular($type): bool
        {
            return $type === 'organisateur';
        }
        function wp_safe_redirect($url, $status = 302, $xRedirectBy = ''): void
        {
            $GLOBALS['cat_test_admin_redirected'] = true;
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Site/SingleHuntPublicAccessHandler.php';

        SingleHuntPublicAccessHandler::redirectClosedEntrances();
        self::assertFalse($GLOBALS['cat_test_admin_redirected']);
    }
}
