<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleDeletionAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleDeletionAjaxHandler.php';

final class RiddleDeletionAjaxHandlerTest extends TestCase {
    public function testRegistersAuthenticatedDeletionAction(): void {
        $hooks = [];

        RiddleDeletionAjaxHandler::register(
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
            'wp_ajax_supprimer_enigme',
            [RiddleDeletionAjaxHandler::class, 'handle'],
            10,
            1,
        ]], $hooks);
    }
}
