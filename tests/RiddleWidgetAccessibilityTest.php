<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RiddleWidgetAccessibilityTest extends TestCase {
    public function testPlayerTemplateProvidesAccessibleSequenceAndDirectionLabels(): void {
        $source = file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/'
            . 'template-parts/enigme/partials/enigme-partial-etapes-joueur.php'
        );

        self::assertSame(5, substr_count($source, "__('Séquence saisie : vide', 'chassesautresor-com')"));
        self::assertStringContainsString('aria-valuetext=', $source);
        self::assertStringContainsString("__('Nord-ouest', 'chassesautresor-com')", $source);
        self::assertStringContainsString('data-label="<?= esc_attr($label); ?>"', $source);
        self::assertStringContainsString('aria-busy="false"', $source);
    }

    public function testPlayerScriptHandlesInterruptedPointerGestures(): void {
        $source = file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/assets/js/riddle-step-player.js'
        );

        self::assertStringContainsString("document.addEventListener('pointercancel'", $source);
        self::assertStringContainsString('setWidgetSequenceLabel(output', $source);
        self::assertStringContainsString("setAttribute('aria-valuetext'", $source);
        self::assertStringContainsString("setAttribute('role', 'alert')", $source);
        self::assertStringContainsString('focusUnlockedContent(target)', $source);
    }
}
