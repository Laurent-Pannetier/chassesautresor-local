<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionDeletionAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionDeletionAjaxHandler.php';

final class SolutionDeletionAjaxHandlerTest extends TestCase {
    public function testRegistersDeletionEndpoint(): void {
        $hooks = [];
        SolutionDeletionAjaxHandler::register(
            static function (
                string $hook,
                array $callback,
                int $priority,
                int $acceptedArgs
            ) use (&$hooks): void {
                $hooks[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertSame([
            ['wp_ajax_supprimer_solution', [SolutionDeletionAjaxHandler::class, 'handle'], 10, 1],
        ], $hooks);
    }
}
