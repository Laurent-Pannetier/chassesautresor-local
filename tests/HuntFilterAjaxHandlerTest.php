<?php

use ChassesAuTresor\Core\Content\HuntFilterAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntFilterAjaxHandler.php';

final class HuntFilterAjaxHandlerTest extends TestCase
{
    public function testItRegistersPublicAndPrivateEndpoints(): void
    {
        $hooks = [];
        HuntFilterAjaxHandler::register(static function (string $hook, callable $callback) use (&$hooks): void {
            $hooks[$hook] = $callback;
        });

        self::assertSame(
            [HuntFilterAjaxHandler::class, 'handle'],
            $hooks['wp_ajax_ca_filter_chasses']
        );
        self::assertSame(
            [HuntFilterAjaxHandler::class, 'handle'],
            $hooks['wp_ajax_nopriv_ca_filter_chasses']
        );
    }
}
