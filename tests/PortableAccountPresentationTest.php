<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PortableAccountPresentationTest extends TestCase {
    private const PLUGIN = __DIR__ . '/../wp-content/plugins/chassesautresor-core';

    public function testRoleDashboardUsesPluginOwnedRenderersAndDirectFallbackLinks(): void {
        $source = (string) file_get_contents(
            self::PLUGIN . '/src/Presentation/PortableAccountDashboardRenderer.php'
        );

        self::assertStringContainsString('AccountSectionRenderer', $source);
        self::assertStringContainsString('AccountOrdersRenderer', $source);
        self::assertStringContainsString("['organisateur', 'organisateur_creation']", $source);
        self::assertStringContainsString("add_query_arg('section'", $source);
        self::assertStringContainsString("get_stylesheet() === 'chassesautresor'", $source);
        self::assertStringNotContainsString('get_template_part', $source);
        self::assertStringNotContainsString('get_stylesheet_directory', $source);
    }

    public function testAccountAjaxTransportRequiresAndReceivesTheSameNonce(): void {
        $handler = (string) file_get_contents(
            self::PLUGIN . '/src/Messages/AccountSectionAjaxHandler.php'
        );
        $assets = (string) file_get_contents(
            self::PLUGIN . '/src/Presentation/FunctionalAssetManager.php'
        );
        $script = (string) file_get_contents(self::PLUGIN . '/assets/js/account.js');

        self::assertStringContainsString("check_ajax_referer('cat_account_section', 'nonce', false)", $handler);
        self::assertStringContainsString("wp_create_nonce('cat_account_section')", $assets);
        self::assertStringContainsString("url.searchParams.set('nonce'", $script);
    }
}
