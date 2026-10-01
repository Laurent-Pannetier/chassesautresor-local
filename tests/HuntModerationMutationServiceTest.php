<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntModerationMutationService;
use PHPUnit\Framework\TestCase;

final class HuntModerationMutationServiceTest extends TestCase
{
    public function testApplyPersistsHuntAndRiddleTransition(): void
    {
        $posts = [];
        $fields = [];
        $hunts = [];
        $riddles = [];
        $plan = [
            'hunt_status' => 'pending',
            'validation_status' => 'correction',
            'riddle_status' => 'pending',
            'riddle_state' => 'bloquee_chasse',
        ];

        (new HuntModerationMutationService())->apply(
            10,
            [20, 21],
            ['existing' => true],
            $plan,
            static function (array $post) use (&$posts): void { $posts[] = $post; },
            static function (string $field, $value, int $id) use (&$fields): void {
                $fields[] = [$field, $value, $id];
            },
            static function (int $id) use (&$hunts): void { $hunts[] = $id; },
            static function (int $id) use (&$riddles): void { $riddles[] = $id; }
        );

        self::assertSame(['ID' => 10, 'post_status' => 'pending'], $posts[0]);
        self::assertContains(['enigme_cache_etat_systeme', 'bloquee_chasse', 20], $fields);
        self::assertSame([10], $hunts);
        self::assertSame([], $riddles);
    }
}
