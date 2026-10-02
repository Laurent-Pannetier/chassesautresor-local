<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleStepPostTypeRegistrar;
use PHPUnit\Framework\TestCase;

if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string {
        return $text;
    }
}

final class RiddleStepPostTypeRegistrarTest extends TestCase {
    public function testRegistersThePostTypeInitializationHook(): void {
        $hooks = [];
        RiddleStepPostTypeRegistrar::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            [['init', [RiddleStepPostTypeRegistrar::class, 'registerPostType']]],
            $hooks
        );
    }

    public function testBuildsAPrivateInspectablePostType(): void {
        $args = RiddleStepPostTypeRegistrar::postTypeArgs();

        self::assertSame('enigme_etape', RiddleStepPostTypeRegistrar::POST_TYPE);
        self::assertFalse($args['public']);
        self::assertFalse($args['publicly_queryable']);
        self::assertTrue($args['exclude_from_search']);
        self::assertTrue($args['show_ui']);
        self::assertFalse($args['show_in_menu']);
        self::assertFalse($args['show_in_rest']);
        self::assertFalse($args['rewrite']);
        self::assertFalse($args['has_archive']);
        self::assertSame(['title', 'author', 'page-attributes'], $args['supports']);
    }
}
