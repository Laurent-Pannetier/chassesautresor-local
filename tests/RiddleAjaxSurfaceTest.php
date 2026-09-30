<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RiddleAjaxSurfaceTest extends TestCase {
    public function testThemeDoesNotExposeUnusedCompletenessEndpoint(): void {
        $source = (string) file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-enigme.php'
        );

        $this->assertStringNotContainsString('wp_ajax_verifier_enigmes_completes', $source);
        $this->assertStringNotContainsString('function verifier_enigmes_completes_ajax()', $source);
    }
}
