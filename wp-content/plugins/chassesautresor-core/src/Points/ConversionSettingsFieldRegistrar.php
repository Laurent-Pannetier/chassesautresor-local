<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

/** Register the ACF options field used to administer the point conversion rate. */
final class ConversionSettingsFieldRegistrar {
    public static function register(callable $addAction): void {
        $addAction('acf/init', [self::class, 'registerFieldGroup']);
    }

    public static function registerFieldGroup(): void {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(self::fieldGroup());
    }

    /** @return array<string,mixed> */
    public static function fieldGroup(): array {
        return [
            'key' => 'group_taux_conversion',
            'title' => __('Paramètres de conversion', 'chassesautresor-com'),
            'fields' => [
                [
                    'key' => 'field_taux_conversion',
                    'label' => __('Taux de conversion actuel', 'chassesautresor-com'),
                    'name' => 'taux_conversion',
                    'type' => 'number',
                    'instructions' => __(
                        'Indiquez le taux de conversion des points en euros (ex. : 0,05 € pour un point).',
                        'chassesautresor-com'
                    ),
                    'default_value' => 0.05,
                    'step' => 0.001,
                    'required' => true,
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'options_taux_conversion',
                    ],
                ],
            ],
        ];
    }
}
