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
        self::assertSame(
            ['texte', 'directions_8'],
            array_keys($fields['etape_widget_type']['choices'])
        );
    }

    public function testConfiguresWidgetSpecificAnswerFields(): void {
        $fields = $this->indexFieldsByName(RiddleStepFieldGroupRegistrar::fieldGroup()['fields']);
        $textAnswers = $fields['etape_reponses_texte'];
        $directions = $fields['etape_directions_sequence'];

        self::assertSame(1, $textAnswers['min']);
        self::assertSame(5, $textAnswers['max']);
        self::assertSame('texte', $textAnswers['conditional_logic'][0][0]['value']);
        self::assertSame(1, $directions['min']);
        self::assertSame(20, $directions['max']);
        self::assertSame('directions_8', $directions['conditional_logic'][0][0]['value']);
        self::assertSame(
            ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'],
            array_keys($directions['sub_fields'][0]['choices'])
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
