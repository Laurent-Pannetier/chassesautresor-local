<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntDeletionAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntDeletionAjaxHandler.php';

final class HuntDeletionAjaxHandlerTest extends TestCase {
    public function testRegistersAuthenticatedDeletionAction(): void {
        $hooks = [];

        HuntDeletionAjaxHandler::register(
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
            'wp_ajax_supprimer_chasse',
            [HuntDeletionAjaxHandler::class, 'handle'],
            10,
            1,
        ]], $hooks);
    }
}
