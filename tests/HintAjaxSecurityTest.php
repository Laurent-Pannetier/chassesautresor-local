<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HintAjaxSecurityTest extends TestCase {
    public function testHintFieldMutationUsesDedicatedNonce(): void {
        $themeSource = (string) file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-core.php'
        );
        $handlerSource = (string) file_get_contents(
            __DIR__
                . '/../wp-content/plugins/chassesautresor-core/src/Content/HintFieldMutationAjaxHandler.php'
        );
        $scriptSource = (string) file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/assets/js/core/champ-init.js'
        );
        $modalScriptSource = (string) file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/assets/js/indices-create.js'
        );

        $this->assertStringContainsString("wp_create_nonce('hint_management')", $themeSource);
        $this->assertStringContainsString(
            "check_ajax_referer('hint_management', 'nonce');",
            $handlerSource
        );
        $this->assertStringContainsString('window.indicesCreate?.nonce', $scriptSource);
        $this->assertStringContainsString(
            "data.append('nonce', indicesCreate.nonce || '');",
            $modalScriptSource
        );
    }

}
