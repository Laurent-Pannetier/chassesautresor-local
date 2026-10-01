<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HintUnlockAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HintUnlockAjaxHandler.php';

final class HintUnlockAjaxHandlerTest extends TestCase {
    public function testRegistersAuthenticatedAndAnonymousEndpoints(): void {
        $hooks = [];
        HintUnlockAjaxHandler::register(static function ($hook, $callback) use (&$hooks): void {
            $hooks[$hook] = $callback;
        });

        $callback = [HintUnlockAjaxHandler::class, 'handle'];
        $this->assertSame($callback, $hooks['wp_ajax_debloquer_indice']);
        $this->assertSame($callback, $hooks['wp_ajax_nopriv_debloquer_indice']);
    }

    public function testAcceptsDeferredThemeRenderer(): void {
        HintUnlockAjaxHandler::configure(static fn (): string => '');
        $this->addToAssertionCount(1);
    }
}
