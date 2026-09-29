<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntFeatureService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntFeatureService.php';

class HuntFeatureServiceTest extends TestCase
{
    private HuntFeatureService $service;

    protected function setUp(): void
    {
        $this->service = new HuntFeatureService();
    }

    public function testDirectHuntFeaturesArePreserved(): void
    {
        $result = $this->service->summarize(true, true, [12], function (): bool {
            return false;
        }, function (): bool {
            return false;
        });

        $this->assertSame(['has_solutions' => true, 'has_indices' => true], $result);
    }

    public function testRiddleFeaturesAreAggregated(): void
    {
        $result = $this->service->summarize(false, false, [12, 24], function (int $id): bool {
            return $id === 24;
        }, function (int $id): bool {
            return $id === 12;
        });

        $this->assertSame(['has_solutions' => true, 'has_indices' => true], $result);
    }

    public function testInvalidRiddleIdsAreIgnored(): void
    {
        $calls = 0;
        $callback = function () use (&$calls): bool {
            $calls++;
            return false;
        };

        $result = $this->service->summarize(false, false, [0, -1], $callback, $callback);

        $this->assertSame(['has_solutions' => false, 'has_indices' => false], $result);
        $this->assertSame(0, $calls);
    }

    public function testAggregationStopsOnceBothFeaturesAreFound(): void
    {
        $visited = [];
        $callback = function (int $id) use (&$visited): bool {
            $visited[] = $id;
            return true;
        };

        $this->service->summarize(false, false, [12, 24], $callback, $callback);

        $this->assertSame([12, 12], $visited);
    }
}
