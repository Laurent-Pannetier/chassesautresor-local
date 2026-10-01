<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntStatisticsAjaxHandler;
use ChassesAuTresor\Core\Progress\RiddleStatisticsAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntStatisticsAjaxHandler.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatisticsAjaxHandler.php';

final class StatisticsAjaxHandlerRegistrationTest extends TestCase {
    public function testRegistersFourAuthenticatedEndpoints(): void {
        $hooks = [];
        $register = static function ($hook, $callback) use (&$hooks): void {
            $hooks[$hook] = $callback;
        };
        HuntStatisticsAjaxHandler::register($register);
        RiddleStatisticsAjaxHandler::register($register);

        $this->assertSame([
            'wp_ajax_chasse_recuperer_stats' => [HuntStatisticsAjaxHandler::class, 'summary'],
            'wp_ajax_chasse_lister_participants' => [HuntStatisticsAjaxHandler::class, 'participants'],
            'wp_ajax_enigme_recuperer_stats' => [RiddleStatisticsAjaxHandler::class, 'summary'],
            'wp_ajax_enigme_lister_participants' => [RiddleStatisticsAjaxHandler::class, 'participants'],
        ], $hooks);
    }

    public function testAcceptsDeferredThemeCallbacks(): void {
        HuntStatisticsAjaxHandler::configure(
            static fn (): string => ''
        );
        RiddleStatisticsAjaxHandler::configure(
            static fn (): string => ''
        );
        $this->addToAssertionCount(1);
    }
}
