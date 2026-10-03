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
                ['wp_ajax_charger_etape_enigme', [RiddleStepManagementAjaxHandler::class, 'load']],
                ['wp_ajax_enregistrer_etape_enigme', [RiddleStepManagementAjaxHandler::class, 'save']],
                ['wp_ajax_supprimer_etape_enigme', [RiddleStepManagementAjaxHandler::class, 'delete']],
                ['wp_ajax_reordonner_etapes_enigme', [RiddleStepManagementAjaxHandler::class, 'reorder']],
            ],
            $hooks
        );
    }

    public function testEverySuccessfulMutationRequestsRiddleCompletenessRefresh(): void {
        $source = file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleStepManagementAjaxHandler.php'
        );

        self::assertSame(
            3,
            substr_count($source, "do_action('chassesautresor_riddle_completeness_refresh_requested', \$riddleId)")
        );
    }
}
