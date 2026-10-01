<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntStatusAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntStatusAjaxHandler.php';

final class HuntStatusAjaxHandlerTest extends TestCase {
    public function testRegistersAuthenticatedStatusEndpointsOnce(): void {
        $hooks = [];
        HuntStatusAjaxHandler::register(
            static function ($hook, $callback, $priority, $acceptedArgs) use (&$hooks): void {
                $hooks[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertSame([
            [
                'wp_ajax_forcer_recalcul_statut_chasse',
                [HuntStatusAjaxHandler::class, 'recalculate'],
                10,
                1,
            ],
            [
                'wp_ajax_recuperer_statut_chasse',
                [HuntStatusAjaxHandler::class, 'getStatus'],
                10,
                1,
            ],
        ], $hooks);
    }
}
