<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\ConversionHistoryAjaxHandler;
use ChassesAuTresor\Core\Points\ConversionModalAjaxHandler;
use ChassesAuTresor\Core\Points\PointsHistoryAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/PointsHistoryAjaxHandler.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/ConversionHistoryAjaxHandler.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/ConversionModalAjaxHandler.php';

final class HistoryAjaxHandlerRegistrationTest extends TestCase {
    public function testRegistersAuthenticatedEndpoints(): void {
        $hooks = [];
        $register = static function ($hook, $callback) use (&$hooks): void {
            $hooks[$hook] = $callback;
        };
        PointsHistoryAjaxHandler::register($register);
        ConversionHistoryAjaxHandler::register($register);
        ConversionModalAjaxHandler::register($register);

        $this->assertSame([
            'wp_ajax_load_points_history' => [PointsHistoryAjaxHandler::class, 'handle'],
            'wp_ajax_load_conversion_history' => [ConversionHistoryAjaxHandler::class, 'handle'],
            'wp_ajax_conversion_modal_content' => [ConversionModalAjaxHandler::class, 'handle'],
        ], $hooks);
    }

    public function testAcceptsDeferredThemeRenderers(): void {
        PointsHistoryAjaxHandler::configure(static fn (): string => '');
        ConversionHistoryAjaxHandler::configure(static fn (): string => '');
        ConversionModalAjaxHandler::configure(static fn (): string => '');
        $this->addToAssertionCount(1);
    }
}
