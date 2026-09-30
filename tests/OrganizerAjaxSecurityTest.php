<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class OrganizerAjaxSecurityTest extends TestCase {
    private string $source;

    protected function setUp(): void {
        $this->source = (string) file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-organisateur.php'
        );
    }

    public function testOrganizerMutationEndpointChecksDedicatedNonce(): void {
        $functionStart = strpos($this->source, 'function ajax_modifier_champ_organisateur()');
        $functionEnd = strpos($this->source, "\n}\n", $functionStart);
        $function = substr($this->source, $functionStart, $functionEnd - $functionStart);

        $this->assertStringContainsString(
            "check_ajax_referer('organizer_management', 'nonce');",
            $function
        );
        $this->assertStringContainsString("wp_create_nonce('organizer_management')", $this->source);
    }

    public function testObsoleteTitleEndpointIsRemoved(): void {
        $this->assertStringNotContainsString('wp_ajax_modifier_titre_organisateur', $this->source);
        $this->assertStringNotContainsString('function modifier_titre_organisateur()', $this->source);
    }

    public function testUnusedOrganizerRedirectFunctionIsRemoved(): void {
        $this->assertStringNotContainsString('function rediriger_selon_etat_organisateur()', $this->source);
    }
}
