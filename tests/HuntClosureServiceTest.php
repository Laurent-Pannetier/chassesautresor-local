<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntClosureService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntClosureService.php';

final class HuntClosureServiceTest extends TestCase {
    public function testIgnoresFieldsThatDoNotCompleteAHunt(): void {
        $calls = [];
        $result = $this->apply('post_title', 'Titre', $calls);

        $this->assertFalse($result['handled']);
        $this->assertNull($result['error']);
        $this->assertSame([], $calls);
    }

    public function testCompletesHuntAndSchedulesAvailableSolutions(): void {
        $calls = [];
        $result = $this->apply('champs_caches.chasse_cache_statut', 'termine', $calls);

        $this->assertTrue($result['handled']);
        $this->assertNull($result['error']);
        $this->assertSame([
            ['field', 'chasse_cache_statut', 'termine', 42],
            ['field', 'chasse_cache_complet', 1, 42],
            ['file', 101],
            ['find', 101, 'enigme'],
            ['schedule', 501],
            ['file', 102],
            ['find', 102, 'enigme'],
            ['find', 42, 'chasse'],
            ['schedule', 900],
            ['players', 42],
        ], $calls);
    }

    public function testStopsWhenACompletionFieldCannotBePersisted(): void {
        $calls = [];
        $result = $this->apply('champs_caches.chasse_cache_statut', 'termine', $calls, false);

        $this->assertTrue($result['handled']);
        $this->assertSame('echec_mise_a_jour', $result['error']);
        $this->assertCount(1, $calls);
    }

    private function apply(string $field, $value, array &$calls, $updateResult = true): array {
        $service = new HuntClosureService();

        return $service->apply(
            42,
            $field,
            $value,
            function (string $name, $storedValue, int $huntId) use (&$calls, $updateResult) {
                $calls[] = ['field', $name, $storedValue, $huntId];
                return $updateResult;
            },
            static fn (int $huntId): array => [101, '102'],
            function (int $riddleId) use (&$calls): void {
                $calls[] = ['file', $riddleId];
            },
            function (int $objectId, string $type) use (&$calls) {
                $calls[] = ['find', $objectId, $type];
                if ($objectId === 101) {
                    return (object) ['ID' => 501];
                }
                if ($type === 'chasse') {
                    return ['ID' => 900];
                }
                return null;
            },
            function (int $solutionId) use (&$calls): void {
                $calls[] = ['schedule', $solutionId];
            },
            function (int $huntId) use (&$calls): void {
                $calls[] = ['players', $huntId];
            }
        );
    }
}
