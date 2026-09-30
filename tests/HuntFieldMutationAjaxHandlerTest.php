<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntFieldMutationAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntFieldMutationAjaxHandler.php';

final class HuntFieldMutationAjaxHandlerTest extends TestCase {
    public function testRegistersFieldMutationEndpoint(): void {
        $hooks = [];

        HuntFieldMutationAjaxHandler::register(
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
                'wp_ajax_modifier_champ_chasse',
                [HuntFieldMutationAjaxHandler::class, 'handle'],
                10,
                1,
            ],
        ], $hooks);
    }
}
