<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\AcfRelationshipMutationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/AcfRelationshipMutationService.php';

final class AcfRelationshipMutationServiceTest extends TestCase {
    public function testAddsRelationshipAndPreservesExistingValues(): void {
        $updates = [];
        $result = (new AcfRelationshipMutationService())->mutate(
            10,
            'related_riddles',
            3,
            'field_related_riddles',
            'add',
            static fn (): array => [1, 2],
            static function (int $postId, string $key, $value) use (&$updates): bool {
                $updates[$key] = $value;
                return true;
            }
        );

        $this->assertTrue($result);
        $this->assertSame([1, 2, 3], $updates['related_riddles']);
        $this->assertSame('field_related_riddles', $updates['_related_riddles']);
    }

    public function testRemovesRelationshipAndReindexesValues(): void {
        $updates = [];
        $result = (new AcfRelationshipMutationService())->mutate(
            10,
            'related_riddles',
            2,
            'field_related_riddles',
            'remove',
            static fn (): array => [1, 2, 3],
            static function (int $postId, string $key, $value) use (&$updates): bool {
                $updates[$key] = $value;
                return true;
            }
        );

        $this->assertTrue($result);
        $this->assertSame([1, 3], $updates['related_riddles']);
    }

    /** @dataProvider invalidMutationProvider */
    public function testRejectsInvalidOrRedundantMutations(
        int $postId,
        string $field,
        int $relatedPostId,
        string $action,
        array $current
    ): void {
        $updates = 0;
        $result = (new AcfRelationshipMutationService())->mutate(
            $postId,
            $field,
            $relatedPostId,
            'field_key',
            $action,
            static fn () => $current,
            static function () use (&$updates): bool {
                $updates++;
                return true;
            }
        );

        $this->assertFalse($result);
        $this->assertSame(0, $updates);
    }

    /** @return array<string, array{int,string,int,string,array<int,int>}> */
    public function invalidMutationProvider(): array {
        return [
            'missing parent' => [0, 'field', 2, 'add', []],
            'missing field' => [1, '', 2, 'add', []],
            'missing relation' => [1, 'field', 0, 'add', []],
            'unknown action' => [1, 'field', 2, 'replace', []],
            'duplicate' => [1, 'field', 2, 'add', [2]],
            'absent removal' => [1, 'field', 2, 'remove', [1]],
        ];
    }
}
