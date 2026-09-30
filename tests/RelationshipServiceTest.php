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
            'nested ACF array' => [[['ID' => 42]], 42],
            'associative ACF value' => [['ID' => 42], 42],
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

    public function testTargetHuntUsesDirectRelationshipForHuntContent(): void
    {
        $service = new RelationshipService();

        $this->assertSame(12, $service->resolveTargetHuntId('chasse', '12', 24));
    }

    public function testTargetHuntUsesRiddleRelationshipForRiddleContent(): void
    {
        $service = new RelationshipService();

        $this->assertSame(
            24,
            $service->resolveTargetHuntId('enigme', 12, (object) ['ID' => 24])
        );
    }

    public function testTargetHuntRejectsUnknownOrInvalidRelationships(): void
    {
        $service = new RelationshipService();

        $this->assertNull($service->resolveTargetHuntId('solution', 12, 24));
        $this->assertNull($service->resolveTargetHuntId('chasse', 0, 24));
        $this->assertNull($service->resolveTargetHuntId('enigme', 12, null));
    }

    public function testHintTargetUsesRelationshipMatchingTargetType(): void
    {
        $service = new RelationshipService();

        $this->assertSame(12, $service->resolveHintTargetId('chasse', 12, 24));
        $this->assertSame(24, $service->resolveHintTargetId('enigme', 12, 24));
        $this->assertNull($service->resolveHintTargetId('solution', 12, 24));
    }
}
