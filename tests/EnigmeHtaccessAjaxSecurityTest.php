<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EnigmeHtaccessAjaxSecurityTest extends TestCase {
    public function testAllHtaccessAjaxControllersVerifyTheEditionNonce(): void {
        $source = file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-securite.php'
        );

        $this->assertIsString($source);
        $this->assertSame(4, substr_count(
            $source,
            "check_ajax_referer('modifier_champ_enigme', 'nonce')"
        ));
        $this->assertSame(1, substr_count(
            $source,
            "add_action('wp_ajax_get_expiration_htaccess_enigme'"
        ));
    }
}
