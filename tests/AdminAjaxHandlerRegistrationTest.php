<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Admin\AdminAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Admin/AdminAjaxHandler.php';

final class AdminAjaxHandlerRegistrationTest extends TestCase
{
    public function testItRegistersAllRemainingAdminEndpoints(): void
    {
        $hooks = [];
        AdminAjaxHandler::register(static function (string $hook, callable $callback) use (&$hooks): void {
            $hooks[$hook] = $callback;
        });

        self::assertSame([
            'wp_ajax_rechercher_utilisateur',
            'wp_ajax_lister_historique_paiements',
            'wp_ajax_update_conversion_status',
            'wp_ajax_recuperer_details_acf',
            'wp_ajax_cta_reset_stats',
            'wp_ajax_cta_toggle_site_protection',
        ], array_keys($hooks));
    }
}
