<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\RiddleImageProtectionAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageProtectionAjaxHandler.php';

final class RiddleImageProtectionAjaxHandlerTest extends TestCase {
    public function testRegistersProtectionEndpointsOnce(): void {
        $hooks = [];
        RiddleImageProtectionAjaxHandler::register(
            static function ($hook, $callback, $priority, $acceptedArgs) use (&$hooks): void {
                $hooks[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertCount(4, $hooks);
        $this->assertSame('wp_ajax_desactiver_htaccess_enigme', $hooks[0][0]);
        $this->assertSame('wp_ajax_reactiver_htaccess_immediat_enigme', $hooks[1][0]);
        $this->assertSame('wp_ajax_get_expiration_htaccess_enigme', $hooks[2][0]);
        $this->assertSame('wp_ajax_verrouillage_termine_enigme', $hooks[3][0]);
    }
}
