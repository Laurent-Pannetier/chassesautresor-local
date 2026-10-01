<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AccountFunctionsTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRecognizesSupportedAccountEndpointsWithoutQueryString(): void {
        function wp_parse_url(string $url, int $component) {
            return parse_url($url, $component);
        }
        function trailingslashit(string $value): string {
            return rtrim($value, '/') . '/';
        }

        require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Users/account-functions.php';
        $_SERVER['REQUEST_URI'] = '/mon-compte/commandes/?page=2';

        self::assertTrue(is_woocommerce_account_page());
    }
}
