<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\ConversionSettingsFieldRegistrar;
use PHPUnit\Framework\TestCase;

final class ConversionSettingsFieldRegistrarTest extends TestCase {
    public function testRegistersTheAcfInitializationHook(): void {
        $hooks = [];
        ConversionSettingsFieldRegistrar::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            [['acf/init', [ConversionSettingsFieldRegistrar::class, 'registerFieldGroup']]],
            $hooks
        );
    }

    public function testBuildsAnInternationalizedConversionFieldGroup(): void {
        $group = ConversionSettingsFieldRegistrar::fieldGroup();

        self::assertSame('group_taux_conversion', $group['key']);
        self::assertSame('field_taux_conversion', $group['fields'][0]['key']);
        self::assertSame('taux_conversion', $group['fields'][0]['name']);
        self::assertSame(0.05, $group['fields'][0]['default_value']);
        self::assertSame('options_taux_conversion', $group['location'][0][0]['value']);
    }
}
