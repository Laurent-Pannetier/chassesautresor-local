<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleCacheMutationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleCacheMutationService.php';

final class RiddleCacheMutationServiceTest extends TestCase {
    private RiddleCacheMutationService $service;

    protected function setUp(): void {
        $this->service = new RiddleCacheMutationService();
    }

    public function testAddsRiddleAndAcfReferenceKey(): void {
        $updates = [];
        $result = $this->service->mutate(
            7,
            12,
            'add',
            static fn (): array => ['5'],
            static function (int $postId, string $key, $value) use (&$updates): bool {
                $updates[] = [$postId, $key, $value];
                return true;
            }
        );

        $this->assertTrue($result);
        $this->assertSame([
            [7, 'chasse_cache_enigmes', ['5', 12]],
            [7, '_chasse_cache_enigmes', 'field_67b740025aae0'],
        ], $updates);
    }

    public function testRemovesAllRepresentationsOfRiddleId(): void {
        $stored = null;
        $result = $this->service->mutate(
            7,
            12,
            'remove',
            static fn (): array => ['12', 5, 12],
            static function (int $postId, string $key, $value) use (&$stored): bool {
                if ($key === 'chasse_cache_enigmes') {
                    $stored = $value;
                }
                return true;
            }
        );

        $this->assertTrue($result);
        $this->assertSame([1 => 5], $stored);
    }

    /** @dataProvider noChangeProvider */
    public function testSkipsInvalidOrUnchangedMutations(
        int $huntId,
        int $riddleId,
        string $action,
        array $current
    ): void {
        $persist = static function (): void {
            self::fail('Unchanged cache must not be persisted.');
        };

        $this->assertFalse($this->service->mutate(
            $huntId,
            $riddleId,
            $action,
            static fn (): array => $current,
            $persist
        ));
    }

    public function noChangeProvider(): array {
        return [
            'invalid hunt' => [0, 12, 'add', []],
            'invalid riddle' => [7, 0, 'add', []],
            'invalid action' => [7, 12, 'replace', []],
            'already attached' => [7, 12, 'add', ['12']],
            'already detached' => [7, 12, 'remove', [5]],
        ];
    }
}
