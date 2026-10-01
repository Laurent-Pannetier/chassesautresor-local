<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleStatusAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatusAjaxHandler.php';

final class RiddleStatusAjaxHandlerTest extends TestCase {
    public function testRegistersAuthenticatedRiddleStatusEndpointOnce(): void {
        $hooks = [];
        RiddleStatusAjaxHandler::register(
            static function ($hook, $callback, $priority, $acceptedArgs) use (&$hooks): void {
                $hooks[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertSame([[
            'wp_ajax_forcer_recalcul_statut_enigme',
            [RiddleStatusAjaxHandler::class, 'handle'],
            10,
            1,
        ]], $hooks);
    }
}
