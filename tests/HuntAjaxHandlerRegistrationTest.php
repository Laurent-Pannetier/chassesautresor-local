<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntNavigationAjaxHandler;
use ChassesAuTresor\Core\Progress\HuntValidationAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntNavigationAjaxHandler.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntValidationAjaxHandler.php';

final class HuntAjaxHandlerRegistrationTest extends TestCase {
    public function testRegistersFiveEndpoints(): void {
        $hooks = [];
        $register = static function ($hook, $callback) use (&$hooks): void {
            $hooks[$hook] = $callback;
        };
        HuntNavigationAjaxHandler::register($register);
        HuntValidationAjaxHandler::register($register);

        $this->assertSame([
            'wp_ajax_chasse_recuperer_navigation' => [HuntNavigationAjaxHandler::class, 'handle'],
            'wp_ajax_nopriv_chasse_recuperer_navigation' => [HuntNavigationAjaxHandler::class, 'handle'],
            'wp_ajax_annulation_validation_chasse' => [HuntValidationAjaxHandler::class, 'cancel'],
            'wp_ajax_nopriv_annulation_validation_chasse' => [HuntValidationAjaxHandler::class, 'cancel'],
            'wp_ajax_actualiser_cta_validation_chasse' => [HuntValidationAjaxHandler::class, 'refreshCta'],
        ], $hooks);
    }

    public function testAcceptsDeferredThemeCallbacks(): void {
        HuntNavigationAjaxHandler::configure(static fn (): bool => false, static fn (): array => []);
        HuntValidationAjaxHandler::configure(
            static fn (): bool => false,
            static fn (): int => 0,
            static function (): void {},
            static fn (): string => ''
        );
        $this->addToAssertionCount(1);
    }
}
