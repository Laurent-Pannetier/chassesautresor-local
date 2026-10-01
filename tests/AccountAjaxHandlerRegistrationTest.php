<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Messages\AccountMessageDismissalAjaxHandler;
use ChassesAuTresor\Core\Messages\AccountSectionAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Messages/AccountMessageDismissalAjaxHandler.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Messages/AccountSectionAjaxHandler.php';

final class AccountAjaxHandlerRegistrationTest extends TestCase {
    public function testAcceptsLazyThemeCallbacks(): void {
        AccountSectionAjaxHandler::configure(
            static fn (): string => '',
            static fn (): array => []
        );
        $this->addToAssertionCount(1);
    }

    public function testRegistersAuthenticatedEndpoints(): void {
        $hooks = [];
        $register = static function ($hook, $callback) use (&$hooks): void {
            $hooks[$hook] = $callback;
        };
        AccountMessageDismissalAjaxHandler::register($register);
        AccountSectionAjaxHandler::register($register);

        $this->assertSame([
            'wp_ajax_cta_dismiss_message' => [AccountMessageDismissalAjaxHandler::class, 'handle'],
            'wp_ajax_cta_load_admin_section' => [AccountSectionAjaxHandler::class, 'handle'],
        ], $hooks);
    }
}
