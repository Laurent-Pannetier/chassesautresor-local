<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleOrderingAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleOrderingAjaxHandler.php';

final class RiddleOrderingAjaxHandlerTest extends TestCase {
    public function testRegistersAuthenticatedAjaxAction(): void {
        $hooks = [];

        RiddleOrderingAjaxHandler::register(
            static function (
                string $hook,
                array $callback,
                int $priority,
                int $acceptedArgs
            ) use (&$hooks): void {
                $hooks[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertSame([[
            'wp_ajax_reordonner_enigmes',
            [RiddleOrderingAjaxHandler::class, 'handle'],
            10,
            1,
        ]], $hooks);
    }
}
