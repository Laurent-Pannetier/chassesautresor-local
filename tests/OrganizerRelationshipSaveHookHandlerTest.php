<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\OrganizerRelationshipSaveHookHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/OrganizerRelationshipSaveHookHandler.php';

final class OrganizerRelationshipSaveHookHandlerTest extends TestCase {
    public function testRegistersOrganizerRelationshipLifecycleOnce(): void {
        $hooks = [];

        OrganizerRelationshipSaveHookHandler::register(
            static function ($hook, $callback, $priority, $acceptedArgs) use (&$hooks): void {
                $hooks[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertSame([[
            'acf/save_post',
            [OrganizerRelationshipSaveHookHandler::class, 'handle'],
            20,
            1,
        ]], $hooks);
    }
}
