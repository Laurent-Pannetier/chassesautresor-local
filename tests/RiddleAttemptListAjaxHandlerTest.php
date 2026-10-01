<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAttemptListAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAttemptListAjaxHandler.php';

final class RiddleAttemptListAjaxHandlerTest extends TestCase {
    public function testRegistersAuthenticatedEndpointOnly(): void {
        $hooks = [];
        RiddleAttemptListAjaxHandler::register(static function ($hook, $callback) use (&$hooks): void {
            $hooks[$hook] = $callback;
        });

        $this->assertSame([
            'wp_ajax_lister_tentatives_enigme' => [RiddleAttemptListAjaxHandler::class, 'handle'],
        ], $hooks);
    }
}
