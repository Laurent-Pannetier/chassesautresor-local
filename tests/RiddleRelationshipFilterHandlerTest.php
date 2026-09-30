<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleRelationshipFilterHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleRelationshipFilterHandler.php';

final class RiddleRelationshipFilterHandlerTest extends TestCase {
    public function testRegistersAcfRelationshipFilters(): void {
        $filters = [];

        RiddleRelationshipFilterHandler::register(
            static function (
                string $hook,
                array $callback,
                int $priority,
                int $acceptedArgs
            ) use (&$filters): void {
                $filters[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertSame([
            [
                'acf/load_field/name=chasse_associee',
                [RiddleRelationshipFilterHandler::class, 'prefillHuntField'],
                10,
                1,
            ],
            [
                'acf/fields/relationship/query',
                [RiddleRelationshipFilterHandler::class, 'limitPrerequisiteChoices'],
                10,
                3,
            ],
        ], $filters);
    }
}
