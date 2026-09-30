<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntDateMutationAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntDateMutationAjaxHandler.php';

final class HuntDateMutationAjaxHandlerTest extends TestCase {
    public function testRegistersDateMutationEndpoint(): void {
        $hooks = [];

        HuntDateMutationAjaxHandler::register(
            static function (
                string $hook,
                array $callback,
                int $priority,
                int $acceptedArgs
            ) use (&$hooks): void {
                $hooks[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertSame([
            [
                'wp_ajax_modifier_dates_chasse',
                [HuntDateMutationAjaxHandler::class, 'handle'],
                10,
                1,
            ],
        ], $hooks);
    }

    public function testParsesSupportedDateInUtcWithoutWordPress(): void {
        $date = HuntDateMutationAjaxHandler::parseDate('2026-09-30 14:15', ['Y-m-d H:i']);

        $this->assertNotNull($date);
        $this->assertSame('2026-09-30 14:15:00', $date->format('Y-m-d H:i:s'));
    }
}
