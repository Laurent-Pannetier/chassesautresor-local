<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ConversionSettingsFunctionsTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLegacyFunctionsUseTheCoreSettingsService(): void {
        $GLOBALS['conversion_options'] = ['taux_conversion' => 42.5];
        function get_option(string $name, $default = false) {
            return $GLOBALS['conversion_options'][$name] ?? $default;
        }
        function update_option(string $name, $value): bool {
            $GLOBALS['conversion_options'][$name] = $value;

            return true;
        }
        function current_time(): string {
            return '2026-10-01 12:00:00';
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Points/ConversionSettingsService.php';
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Points/conversion-settings-functions.php';

        self::assertSame(42.5, get_taux_conversion_actuel());

        update_taux_conversion(51.25);

        self::assertSame(51.25, $GLOBALS['conversion_options']['taux_conversion']);
        self::assertSame(
            51.25,
            $GLOBALS['conversion_options']['historique_taux_conversion'][0]['valeur_taux_conversion']
        );
    }
}
