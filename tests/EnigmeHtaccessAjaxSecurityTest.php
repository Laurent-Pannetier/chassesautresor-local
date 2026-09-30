<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EnigmeHtaccessAjaxSecurityTest extends TestCase {
    public function testCoreControllerUsesNonceAndPermissionAdapter(): void {
        $source = (string) file_get_contents(
            __DIR__
                . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageProtectionAjaxHandler.php'
        );

        $this->assertStringContainsString(
            "check_ajax_referer('modifier_champ_enigme', 'nonce')",
            $source
        );
        $this->assertStringContainsString('chassesautresor_can_manage_riddle_images', $source);
        $this->assertStringContainsString("current_user_can('edit_posts')", $source);
    }
}
