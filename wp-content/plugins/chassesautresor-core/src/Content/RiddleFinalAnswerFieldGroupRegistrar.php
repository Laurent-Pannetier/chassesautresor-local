<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Register ACF fields used by the shared final-answer widget engine. */
final class RiddleFinalAnswerFieldGroupRegistrar {
    public static function register(callable $addAction): void {
        $addAction('acf/init', [self::class, 'registerFieldGroup']);
    }

    public static function registerFieldGroup(): void {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(self::fieldGroup());
    }

    /** @return array<string, mixed> */
    public static function fieldGroup(): array {
        return [
            'key' => 'group_enigme_reponse_widget',
            'title' => __('Réponse finale automatique', 'chassesautresor-com'),
            'fields' => self::fields(),
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'enigme',
                    ],
                ],
            ],
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => true,
            'show_in_rest' => false,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function fields(): array {
        return [
            [
                'key' => 'field_enigme_reponse_widget',
                'label' => __('Mode de réponse automatique', 'chassesautresor-com'),
                'name' => 'enigme_reponse_widget',
                'type' => 'select',
                'required' => false,
                'choices' => [
                    'text' => __('Réponse texte', 'chassesautresor-com'),
                    'directions' => __('Pavé à huit directions', 'chassesautresor-com'),
                    'colors' => __('Clavier de couleurs', 'chassesautresor-com'),
                    'numbers' => __('Pavé numérique', 'chassesautresor-com'),
                    'safe_dial' => __('Molette de coffre-fort', 'chassesautresor-com'),
                    'piano' => __('Piano', 'chassesautresor-com'),
                    'gps' => __('Coordonnées GPS', 'chassesautresor-com'),
                ],
                'default_value' => 'text',
                'return_format' => 'value',
            ],
            [
                'key' => 'field_enigme_gps_coordinates',
                'label' => __('Coordonnées GPS attendues', 'chassesautresor-com'),
                'name' => 'enigme_gps_coordinates',
                'type' => 'text',
                'required' => false,
            ],
            [
                'key' => 'field_enigme_gps_tolerance',
                'label' => __('Tolérance GPS en mètres', 'chassesautresor-com'),
                'name' => 'enigme_gps_tolerance',
                'type' => 'number',
                'required' => false,
                'default_value' => 25,
                'min' => 1,
                'max' => 100000,
            ],
            [
                'key' => 'field_enigme_number_sequences',
                'label' => __('Codes numériques acceptés', 'chassesautresor-com'),
                'name' => 'enigme_number_sequences',
                'type' => 'textarea',
                'required' => false,
                'rows' => 4,
            ],
            [
                'key' => 'field_enigme_safe_dial_sequences',
                'label' => __('Codes de molette acceptés', 'chassesautresor-com'),
                'name' => 'enigme_safe_dial_sequences',
                'type' => 'textarea',
                'required' => false,
                'rows' => 4,
            ],
            [
                'key' => 'field_enigme_piano_sequences',
                'label' => __('Séquences musicales acceptées', 'chassesautresor-com'),
                'name' => 'enigme_piano_sequences',
                'type' => 'textarea',
                'required' => false,
                'rows' => 4,
            ],
            [
                'key' => 'field_enigme_color_sequences',
                'label' => __('Codes couleur acceptés', 'chassesautresor-com'),
                'name' => 'enigme_color_sequences',
                'type' => 'textarea',
                'required' => false,
                'rows' => 4,
            ],
            [
                'key' => 'field_enigme_directions_sequences',
                'label' => __('Codes directionnels acceptés', 'chassesautresor-com'),
                'name' => 'enigme_directions_sequences',
                'type' => 'textarea',
                'required' => false,
                'rows' => 4,
            ],
        ];
    }
}
