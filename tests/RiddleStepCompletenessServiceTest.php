<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleStepCompletenessService;
use PHPUnit\Framework\TestCase;

final class RiddleStepCompletenessServiceTest extends TestCase {
    /** @dataProvider completeWidgetProvider */
    public function testAcceptsCompleteStepForEveryWidget(string $widget, array $fields): void {
        $values = array_merge($this->baseFields(), $fields, ['etape_reponse_widget' => $widget]);
        $service = new RiddleStepCompletenessService();

        self::assertTrue($service->areStepsComplete(
            [12],
            static fn (int $stepId): string => 'Étape complète',
            static fn (string $field, int $stepId) => $values[$field] ?? ''
        ));
    }

    public function completeWidgetProvider(): array {
        return [
            'click' => ['click', ['etape_reponse_bouton' => 'Continuer', 'etape_contenu' => 'Observez.']],
            'text' => ['text', ['etape_reponses_texte' => 'étoile', 'etape_contenu' => 'Répondez.']],
            'directions' => ['directions', ['etape_directions_sequences' => 'N,E,S,O']],
            'colors' => ['colors', ['etape_color_sequences' => 'red,blue']],
            'numbers' => ['numbers', ['etape_number_sequences' => '0129']],
            'safe dial' => ['safe_dial', ['etape_safe_dial_sequences' => 'H11 A51']],
            'piano' => ['piano', ['etape_piano_sequences' => 'F1 F#2 B2 C1']],
            'gps' => ['gps', [
                'etape_gps_coordinates' => '48.858370 2.294481',
                'etape_gps_tolerance' => '25',
            ]],
        ];
    }

    public function testRejectsIncompleteContentAndMalformedWidgetConfiguration(): void {
        $service = new RiddleStepCompletenessService();
        $click = array_merge($this->baseFields(), [
            'etape_reponse_widget' => 'click',
            'etape_reponse_bouton' => 'Continuer',
        ]);
        $directions = array_merge($this->baseFields(), [
            'etape_reponse_widget' => 'directions',
            'etape_directions_sequences' => 'N,INCONNU,E',
        ]);
        $hotspot = array_merge($this->baseFields(), [
            'etape_reponse_widget' => 'safe_dial',
            'etape_safe_dial_sequences' => 'H11 A51',
            'etape_widget_affichage' => 'hotspot',
            'etape_image' => 0,
        ]);

        self::assertFalse($service->areStepsComplete(
            [12],
            static fn (int $stepId): string => 'Étape sans contenu',
            static fn (string $field, int $stepId) => $click[$field] ?? ''
        ));
        self::assertFalse($service->areStepsComplete(
            [13],
            static fn (int $stepId): string => 'Étape mal configurée',
            static fn (string $field, int $stepId) => $directions[$field] ?? ''
        ));
        self::assertFalse($service->areStepsComplete(
            [14],
            static fn (int $stepId): string => 'Étape hotspot invalide',
            static fn (string $field, int $stepId) => $hotspot[$field] ?? ''
        ));
    }

    public function testAcceptsHotspotModeWithImageAndZone(): void {
        $service = new RiddleStepCompletenessService();
        $fields = array_merge($this->baseFields(), [
            'etape_reponse_widget' => 'safe_dial',
            'etape_safe_dial_sequences' => 'H11 A51',
            'etape_widget_affichage' => 'hotspot',
            'etape_image' => 18,
            'etape_hotspot_zone' => '40,55,20,25',
            'etape_hotspot_label' => 'Molette',
        ]);

        self::assertTrue($service->areStepsComplete(
            [12],
            static fn (int $stepId): string => 'Porte',
            static fn (string $field, int $stepId) => $fields[$field] ?? ''
        ));
    }

    public function testEmptyOptionalPathIsCompleteAndEvaluationStopsAtFirstInvalidStep(): void {
        $calls = [];
        $service = new RiddleStepCompletenessService();

        self::assertTrue($service->areStepsComplete([]));
        self::assertFalse($service->areStepsComplete(
            [12, 13],
            static fn (int $stepId): string => '',
            static function (string $field, int $stepId) use (&$calls): string {
                $calls[] = $stepId;
                return '';
            }
        ));
        self::assertContains(12, $calls);
        self::assertNotContains(13, $calls);
    }

    private function baseFields(): array {
        return [
            'etape_reponse_widget' => '',
            'etape_reponse_bouton' => '',
            'etape_reponses_texte' => '',
            'etape_reponse_casse' => false,
            'etape_reponses_variantes' => '',
            'etape_directions_sequences' => '',
            'etape_color_sequences' => '',
            'etape_number_sequences' => '',
            'etape_safe_dial_sequences' => '',
            'etape_piano_sequences' => '',
            'etape_gps_coordinates' => '',
            'etape_gps_tolerance' => '',
            'etape_contenu' => '',
            'etape_image' => 0,
            'etape_widget_affichage' => 'always',
            'etape_hotspot_zone' => '',
            'etape_hotspot_label' => '',
        ];
    }
}
