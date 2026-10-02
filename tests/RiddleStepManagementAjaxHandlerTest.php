<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleStepManagementAjaxHandler;
use PHPUnit\Framework\TestCase;

final class RiddleStepManagementAjaxHandlerTest extends TestCase {
    public function testRegistersAuthenticatedManagementActions(): void {
        $hooks = [];
        RiddleStepManagementAjaxHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            [
                ['wp_ajax_creer_etape_enigme', [RiddleStepManagementAjaxHandler::class, 'create']],
                ['wp_ajax_supprimer_etape_enigme', [RiddleStepManagementAjaxHandler::class, 'delete']],
                ['wp_ajax_reordonner_etapes_enigme', [RiddleStepManagementAjaxHandler::class, 'reorder']],
            ],
            $hooks
        );
    }
}
