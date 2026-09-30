<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HuntAjaxSecurityTest extends TestCase {
    public function testHuntMutationEndpointsUseDedicatedNonce(): void {
        $source = (string) file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-chasse.php'
        );
        $dateHandlerSource = (string) file_get_contents(
            __DIR__
                . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntDateMutationAjaxHandler.php'
        );
        $fieldHandlerSource = (string) file_get_contents(
            __DIR__
                . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntFieldMutationAjaxHandler.php'
        );

        $this->assertStringContainsString(
            "wp_create_nonce('hunt_field_management')",
            $source
        );
        $this->assertSame(
            0,
            substr_count($source, "check_ajax_referer('hunt_field_management', 'nonce');")
        );
        $this->assertStringContainsString(
            "check_ajax_referer('hunt_field_management', 'nonce');",
            $dateHandlerSource
        );
        $this->assertStringContainsString(
            "check_ajax_referer('hunt_field_management', 'nonce');",
            $fieldHandlerSource
        );
    }
}
