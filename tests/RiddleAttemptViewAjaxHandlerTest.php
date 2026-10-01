<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAttemptViewAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAttemptViewAjaxHandler.php';

final class RiddleAttemptViewAjaxHandlerTest extends TestCase {
    public function testRegistersAuthenticatedAndAnonymousEndpoints(): void {
        $hooks = [];
        RiddleAttemptViewAjaxHandler::register(static function ($hook, $callback) use (&$hooks): void {
            $hooks[$hook] = $callback;
        });

        $callback = [RiddleAttemptViewAjaxHandler::class, 'handle'];
        $this->assertSame($callback, $hooks['wp_ajax_ca_view_tentative_proposition']);
        $this->assertSame($callback, $hooks['wp_ajax_nopriv_ca_view_tentative_proposition']);
    }
}
