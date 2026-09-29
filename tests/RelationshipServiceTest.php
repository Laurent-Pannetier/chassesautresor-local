<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Relationships\RelationshipService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';

class RelationshipServiceTest extends TestCase
{
    /**
     * @dataProvider relationshipIdProvider
     *
     * @param mixed $value
     */
    public function testRelationshipIdIsNormalized($value, ?int $expected): void
    {
        $this->assertSame($expected, (new RelationshipService())->normalizeId($value));
    }

    public function relationshipIdProvider(): array
    {
        return [
            'integer' => [42, 42],
            'numeric string' => ['42', 42],
            'object' => [(object) ['ID' => 42], 42],
            'array of IDs' => [[42], 42],
            'array of objects' => [[(object) ['ID' => 42]], 42],
            'empty array' => [[], null],
            'zero' => [0, null],
            'invalid object' => [(object) ['post_id' => 42], null],
        ];
    }

    public function testRelationshipListIsNormalizedWithoutHidingDuplicates(): void
    {
        $service = new RelationshipService();

        $this->assertSame(
            [12, 24, 12],
            $service->normalizeIds([12, (object) ['ID' => 24], 'invalid', '12', 0])
        );
    }
}
