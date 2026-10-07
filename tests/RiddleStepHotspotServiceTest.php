<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleStepHotspotService;
use PHPUnit\Framework\TestCase;

if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string {
        return $text;
    }
}

final class RiddleStepHotspotServiceTest extends TestCase {
    public function testParsesAndFormatsPercentageZones(): void {
        $service = new RiddleStepHotspotService();

        self::assertSame(
            ['x' => 40.0, 'y' => 55.5, 'w' => 20.0, 'h' => 25.0],
            $service->parseZone('40, 55.5, 20, 25')
        );
        self::assertSame('40,55.5,20,25', $service->formatZone([
            'x' => 40.0,
            'y' => 55.5,
            'w' => 20.0,
            'h' => 25.0,
        ]));
        self::assertNull($service->parseZone('10,10,1,20'));
        self::assertNull($service->parseZone('90,10,20,20'));
        self::assertNotNull($service->parseZone('10,10,2,3'));
    }

    public function testHotspotModeRequiresImageAndZone(): void {
        $service = new RiddleStepHotspotService();

        $missingImage = $service->validate('hotspot', '40,55,20,25', 'Ouvrir', 0);
        $missingZone = $service->validate('hotspot', '', 'Ouvrir', 12);
        $valid = $service->validate('hotspot', '40,55,20,25', '', 12);

        self::assertInstanceOf(WP_Error::class, $missingImage);
        self::assertInstanceOf(WP_Error::class, $missingZone);
        self::assertIsArray($valid);
        self::assertTrue($valid['active']);
        self::assertSame('Zone interactive', $valid['label']);
    }

    public function testAlwaysModeKeepsOptionalZoneAndIsInactive(): void {
        $service = new RiddleStepHotspotService();
        $result = $service->validate('always', '40,55,20,25', 'Molette', 12);

        self::assertIsArray($result);
        self::assertSame(RiddleStepHotspotService::MODE_ALWAYS, $result['mode']);
        self::assertFalse($result['active']);
        self::assertSame('Molette', $result['label']);
        self::assertNotNull($result['zone']);
    }

    public function testForStepResolvesActiveHotspotFromStoredFields(): void {
        $service = new RiddleStepHotspotService();
        $fields = [
            'etape_image' => 9,
            'etape_widget_affichage' => 'hotspot',
            'etape_hotspot_zone' => '10,20,30,40',
            'etape_hotspot_label' => 'Serrure',
        ];

        $result = $service->forStep(
            3,
            static fn (string $field, int $stepId) => $fields[$field] ?? ''
        );

        self::assertTrue($result['active']);
        self::assertSame('10,20,30,40', $result['zone_raw']);
        self::assertSame('Serrure', $result['label']);
    }

    public function testPersistWritesNormalizedFields(): void {
        $service = new RiddleStepHotspotService();
        $written = [];
        $service->persist(
            4,
            [
                'mode' => RiddleStepHotspotService::MODE_HOTSPOT,
                'zone' => ['x' => 11.0, 'y' => 22.0, 'w' => 33.0, 'h' => 12.5],
                'label' => 'Porte',
                'active' => true,
            ],
            static function (string $field, $value, int $stepId) use (&$written): void {
                $written[$field] = $value;
            }
        );

        self::assertSame('hotspot', $written['etape_widget_affichage']);
        self::assertSame('11,22,33,12.5', $written['etape_hotspot_zone']);
        self::assertSame('Porte', $written['etape_hotspot_label']);
    }
}
