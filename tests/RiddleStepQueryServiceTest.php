<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleStepPostTypeRegistrar;
use ChassesAuTresor\Core\Content\RiddleStepQueryService;
use PHPUnit\Framework\TestCase;

final class RiddleStepQueryServiceTest extends TestCase {
    public function testBuildsAnOrderedPrivateStepQuery(): void {
        $service = new RiddleStepQueryService();
        $args = $service->getOrderedIdsQueryArgs(42);

        self::assertSame(RiddleStepPostTypeRegistrar::POST_TYPE, $args['post_type']);
        self::assertSame('ids', $args['fields']);
        self::assertSame(-1, $args['posts_per_page']);
        self::assertSame(42, $args['meta_query'][0]['value']);
        self::assertSame(['menu_order' => 'ASC', 'ID' => 'ASC'], $args['orderby']);
        self::assertSame([], $service->getOrderedIdsQueryArgs(0));
    }

    public function testNormalizesIdsReturnedByWordPress(): void {
        $service = new RiddleStepQueryService();
        $receivedArgs = [];
        $ids = $service->findOrderedIds(
            42,
            static function (array $args) use (&$receivedArgs): array {
                $receivedArgs = $args;
                return ['8', 9, 0];
            }
        );

        self::assertSame([8, 9], $ids);
        self::assertSame(42, $receivedArgs['meta_query'][0]['value']);
    }
}
