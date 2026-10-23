<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Register the player-access fields stored on a hint. */
final class HintAccessFieldGroupRegistrar {
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
            'key' => 'group_indice_acces_joueur',
            'title' => __('Accès joueur de l’indice', 'chassesautresor-com'),
            'fields' => [
                [
                    'key' => 'field_indice_acces_pre_requis',
                    'label' => __('Prérequis consultés', 'chassesautresor-com'),
                    'name' => 'indice_acces_pre_requis',
                    'type' => 'relationship',
                    'instructions' => __(
                        'Le joueur doit avoir consulté chacun de ces indices.',
                        'chassesautresor-com'
                    ),
                    'post_type' => ['indice'],
                    'filters' => ['search'],
                    'return_format' => 'id',
                    'min' => 0,
                    'max' => 0,
                ],
                [
                    'key' => 'field_indice_delai_minutes',
                    'label' => __('Délai en minutes', 'chassesautresor-com'),
                    'name' => 'indice_delai_minutes',
                    'type' => 'number',
                    'instructions' => __('0 n’ajoute aucun délai.', 'chassesautresor-com'),
                    'min' => 0,
                    'max' => HintAccessPolicy::MAX_DELAY_MINUTES,
                    'step' => 1,
                    'default_value' => 0,
                ],
                [
                    'key' => 'field_indice_delai_ancrage',
                    'label' => __('Ancrage du délai', 'chassesautresor-com'),
                    'name' => 'indice_delai_ancrage',
                    'type' => 'select',
                    'choices' => [
                        HintAccessPolicy::ANCHOR_NONE => __('Aucun', 'chassesautresor-com'),
                        HintAccessPolicy::ANCHOR_ENGAGEMENT => __(
                            'Premier engagement',
                            'chassesautresor-com'
                        ),
                        HintAccessPolicy::ANCHOR_PREREQUISITE => __(
                            'Dernier prérequis consulté',
                            'chassesautresor-com'
                        ),
                    ],
                    'default_value' => HintAccessPolicy::ANCHOR_NONE,
                    'return_format' => 'value',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'indice',
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
}
