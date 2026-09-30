<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleRelationshipCleanupService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleRelationshipService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleRelationshipCleanupService.php';

class RiddleRelationshipCleanupServiceTest extends TestCase {
    public function testOnlyChangedValidRelationshipRowsArePersisted(): void {
        $persisted = [];
        $rows = [
            (object) ['post_id' => 7, 'meta_value' => serialize([10, 20, 20])],
            (object) ['post_id' => 8, 'meta_value' => serialize([10, 30])],
            (object) ['post_id' => 9, 'meta_value' => 'invalid'],
            (object) ['post_id' => 10],
        ];

        $updated = (new RiddleRelationshipCleanupService())->clean(
            $rows,
            static function (string $value) {
                return str_starts_with($value, 'a:') ? unserialize($value) : $value;
            },
            static fn (int $id): bool => in_array($id, [10, 30], true),
            static function (int $huntId, array $ids) use (&$persisted): void {
                $persisted[$huntId] = $ids;
            }
        );

        $this->assertSame(1, $updated);
        $this->assertSame([7 => [10]], $persisted);
    }
}
