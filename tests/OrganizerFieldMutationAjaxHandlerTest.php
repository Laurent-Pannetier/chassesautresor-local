<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\OrganizerFieldMutationAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/OrganizerFieldMutationAjaxHandler.php';

final class OrganizerFieldMutationAjaxHandlerTest extends TestCase {
    public function testRegistersFieldMutationEndpoint(): void {
        $hooks = [];

        OrganizerFieldMutationAjaxHandler::register(
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
                'wp_ajax_modifier_champ_organisateur',
                [OrganizerFieldMutationAjaxHandler::class, 'handle'],
                10,
                1,
            ],
        ], $hooks);
    }
}
