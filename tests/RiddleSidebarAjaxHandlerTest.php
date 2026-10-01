<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleSidebarAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleSidebarAjaxHandler.php';

final class RiddleSidebarAjaxHandlerTest extends TestCase {
    public function testRegistersFourEndpoints(): void {
        $hooks = [];
        RiddleSidebarAjaxHandler::register(static function ($hook, $callback) use (&$hooks): void {
            $hooks[$hook] = $callback;
        });
        $this->assertSame([
            'wp_ajax_enigme_recuperer_gagnants' => [RiddleSidebarAjaxHandler::class, 'winners'],
            'wp_ajax_nopriv_enigme_recuperer_gagnants' => [RiddleSidebarAjaxHandler::class, 'winners'],
            'wp_ajax_enigme_recuperer_progression' => [RiddleSidebarAjaxHandler::class, 'progression'],
            'wp_ajax_nopriv_enigme_recuperer_progression' => [RiddleSidebarAjaxHandler::class, 'progression'],
        ], $hooks);
    }

    public function testAcceptsDeferredThemeCallbacks(): void {
        RiddleSidebarAjaxHandler::configure(
            static fn (): string => '',
            static fn (): string => ''
        );
        $this->addToAssertionCount(1);
    }
}
