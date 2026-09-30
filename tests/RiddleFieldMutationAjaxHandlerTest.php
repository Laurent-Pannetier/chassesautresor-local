<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleFieldMutationAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleFieldMutationAjaxHandler.php';

final class RiddleFieldMutationAjaxHandlerTest extends TestCase {
    public function testRegistersFieldMutationEndpoint(): void {
        $hooks = [];

        RiddleFieldMutationAjaxHandler::register(
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
            [
                'wp_ajax_modifier_champ_enigme',
                [RiddleFieldMutationAjaxHandler::class, 'handle'],
                10,
                1,
            ],
        ], $hooks);
    }
}
