<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\EngagedHuntsAjaxHandler;
use ChassesAuTresor\Core\Progress\UserAttemptsAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/EngagedHuntsAjaxHandler.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/UserAttemptsAjaxHandler.php';

final class UserProgressAjaxHandlerRegistrationTest extends TestCase {
    public function testRegistersEndpoints(): void {
        $hooks = [];
        $register = static function ($hook, $callback) use (&$hooks): void {
            $hooks[$hook] = $callback;
        };
        EngagedHuntsAjaxHandler::register($register);
        UserAttemptsAjaxHandler::register($register);

        $this->assertSame([
            'wp_ajax_ca_get_engaged_hunts' => [EngagedHuntsAjaxHandler::class, 'handle'],
            'wp_ajax_ca_fetch_tentatives' => [UserAttemptsAjaxHandler::class, 'handle'],
            'wp_ajax_nopriv_ca_fetch_tentatives' => [UserAttemptsAjaxHandler::class, 'handle'],
        ], $hooks);
    }

    public function testAcceptsDeferredThemeCallbacks(): void {
        EngagedHuntsAjaxHandler::configure(
            static fn (): string => ''
        );
        UserAttemptsAjaxHandler::configure(
            static function (): void {},
            static fn (): array => [],
            static fn (): string => '',
            static fn (): string => ''
        );
        $this->addToAssertionCount(1);
    }
}
