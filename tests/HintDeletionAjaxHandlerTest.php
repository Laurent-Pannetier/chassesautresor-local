<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintDeletionAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintDeletionAjaxHandler.php';

final class HintDeletionAjaxHandlerTest extends TestCase {
    public function testRegistersDeletionEndpoint(): void {
        $hooks = [];
        HintDeletionAjaxHandler::register(
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
            ['wp_ajax_supprimer_indice', [HintDeletionAjaxHandler::class, 'handle'], 10, 1],
        ], $hooks);
    }
}
