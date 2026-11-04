<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleStepFieldGroupRegistrar;
use ChassesAuTresor\Core\Content\RiddleStepPostTypeRegistrar;
use PHPUnit\Framework\TestCase;

if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string {
        return $text;
    }
}

final class RiddleStepFieldGroupRegistrarTest extends TestCase {
    public function testRegistersTheAcfInitializationHook(): void {
        $hooks = [];
        RiddleStepFieldGroupRegistrar::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            [['acf/init', [RiddleStepFieldGroupRegistrar::class, 'registerFieldGroup']]],
            $hooks
        );
    }

    public function testBuildsTheRiddleStepFieldGroup(): void {
        $group = RiddleStepFieldGroupRegistrar::fieldGroup();
        $fields = $this->indexFieldsByName($group['fields']);

        self::assertSame('group_enigme_etape_configuration', $group['key']);
        self::assertSame(RiddleStepPostTypeRegistrar::POST_TYPE, $group['location'][0][0]['value']);
        self::assertFalse($group['show_in_rest']);
        self::assertSame('id', $fields['etape_enigme_associee']['return_format']);
        self::assertSame('id', $fields['etape_image']['return_format']);
        self::assertSame('gallery', $fields['etape_image']['type']);
        self::assertStringContainsString('1380 px', $fields['etape_image']['instructions']);
        self::assertStringContainsString('hauteur est libre', $fields['etape_image']['instructions']);
        self::assertStringNotContainsString('4000', $fields['etape_image']['instructions']);
        self::assertSame(
            [
                'etape_enigme_associee',
                'etape_contenu',
                'etape_image',
                'etape_reponse_widget',
                'etape_gps_coordinates',
                'etape_gps_tolerance',
                'etape_number_sequences',
                'etape_safe_dial_sequences',
                'etape_piano_sequences',
                'etape_color_sequences',
                'etape_directions_sequences',
                'etape_reponse_bouton',
                'etape_reponses_texte',
                'etape_reponse_casse',
                'etape_reponses_variantes',
            ],
            array_keys($fields)
        );
        self::assertSame(
            ['click', 'text', 'directions', 'colors', 'numbers', 'safe_dial', 'piano', 'gps'],
            array_keys($fields['etape_reponse_widget']['choices'])
        );
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     * @return array<string, array<string, mixed>>
     */
    private function indexFieldsByName(array $fields): array {
        $indexed = [];
        foreach ($fields as $field) {
            $indexed[$field['name']] = $field;
        }

        return $indexed;
    }
}
