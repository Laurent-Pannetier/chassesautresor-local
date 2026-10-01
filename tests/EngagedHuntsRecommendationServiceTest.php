<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\EngagedHuntsRecommendationService;
use PHPUnit\Framework\TestCase;

final class EngagedHuntsRecommendationServiceTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBuildsAUniqueLimitedSelectionAcrossQueries(): void {
        $GLOBALS['recommendation_queries'] = [];
        function apply_filters(string $filter, $value) {
            return $value;
        }
        function get_posts(array $args): array {
            $GLOBALS['recommendation_queries'][] = $args;
            $call = count($GLOBALS['recommendation_queries']);

            return [1 => [10, 20], 2 => [20], 3 => [30]][$call] ?? [];
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/EngagedHuntsRecommendationService.php';

        $ids = (new EngagedHuntsRecommendationService())->find(3);

        self::assertSame([10, 20, 30], $ids);
        self::assertCount(3, $GLOBALS['recommendation_queries']);
        self::assertSame([10, 20], $GLOBALS['recommendation_queries'][1]['post__not_in']);
    }
}
