<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAnswerSubmissionAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAnswerSubmissionAjaxHandler.php';

final class RiddleAnswerSubmissionAjaxHandlerTest extends TestCase {
    public function testRegistersAuthenticatedAndAnonymousSubmissionEndpoints(): void {
        $hooks = [];
        RiddleAnswerSubmissionAjaxHandler::register(
            static function (string $hook, array $callback) use (&$hooks): void {
                $hooks[$hook] = $callback;
            }
        );

        $this->assertSame(
            [RiddleAnswerSubmissionAjaxHandler::class, 'submitManual'],
            $hooks['wp_ajax_soumettre_reponse_manuelle']
        );
        $this->assertSame(
            [RiddleAnswerSubmissionAjaxHandler::class, 'submitManual'],
            $hooks['wp_ajax_nopriv_soumettre_reponse_manuelle']
        );
        $this->assertSame(
            [RiddleAnswerSubmissionAjaxHandler::class, 'submitAutomatic'],
            $hooks['wp_ajax_soumettre_reponse_automatique']
        );
        $this->assertSame(
            [RiddleAnswerSubmissionAjaxHandler::class, 'submitAutomatic'],
            $hooks['wp_ajax_nopriv_soumettre_reponse_automatique']
        );
    }
}
