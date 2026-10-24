<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Versioned ACF schema for trophy rules, recipients and notifications. */
final class TrophyFieldGroupRegistrar
{
    public static function register(callable $addAction): void
    {
        $addAction('acf/init', [self::class, 'registerFieldGroup']);
    }

    public static function registerFieldGroup(): void
    {
        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group(self::fieldGroup());
        }
    }

    /** @return array<string, mixed> */
    public static function fieldGroup(): array
    {
        return [
            'key' => 'group_trophee_configuration',
            'title' => __('Configuration du trophée', 'chassesautresor-com'),
            'fields' => self::fields(),
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => TrophyPostTypeRegistrar::POST_TYPE,
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
    private static function fields(): array
    {
        $registry = new TrophyTriggerRegistry();
        $fields = [
            [
                'key' => 'field_trophee_image',
                'label' => __('Image', 'chassesautresor-com'),
                'name' => 'trophee_image',
                'type' => 'image',
                'instructions' => __(
                    'Image affichée avec le trophée et dans l’overlay.',
                    'chassesautresor-com'
                ),
                'return_format' => 'id',
                'preview_size' => 'medium',
                'library' => 'all',
                'mime_types' => 'jpg, jpeg, png, gif, webp',
            ],
            [
                'key' => 'field_trophee_description',
                'label' => __('Texte court', 'chassesautresor-com'),
                'name' => 'trophee_description',
                'type' => 'textarea',
                'instructions' => __(
                    'Courte explication du trophée, affichée sur la page Trophées.',
                    'chassesautresor-com'
                ),
                'rows' => 3,
                'maxlength' => 300,
            ],
            [
                'key' => 'field_trophee_active',
                'label' => __('Trophée actif', 'chassesautresor-com'),
                'name' => 'trophee_active',
                'type' => 'true_false',
                'default_value' => true,
                'ui' => true,
            ],
            [
                'key' => 'field_trophee_chasse',
                'label' => __('Chasse', 'chassesautresor-com'),
                'name' => 'trophee_chasse',
                'type' => 'post_object',
                'instructions' => __(
                    'Facultatif. Sans chasse, le trophée n’apparaît que sur le mur de l’administrateur.',
                    'chassesautresor-com'
                ),
                'required' => false,
                'post_type' => ['chasse'],
                'taxonomy' => [],
                'allow_null' => true,
                'multiple' => false,
                'return_format' => 'id',
                'ui' => true,
            ],
            [
                'key' => 'field_trophee_scope',
                'label' => __('Attribution', 'chassesautresor-com'),
                'name' => 'trophee_scope',
                'type' => 'select',
                'required' => true,
                'choices' => [
                    'user' => __('Joueur', 'chassesautresor-com'),
                    'team' => __('Équipe', 'chassesautresor-com'),
                    'both' => __('Joueur et équipe', 'chassesautresor-com'),
                ],
                'default_value' => 'user',
                'return_format' => 'value',
            ],
            [
                'key' => 'field_trophee_trigger_type',
                'label' => __('Déclencheur', 'chassesautresor-com'),
                'name' => 'trophee_trigger_type',
                'type' => 'select',
                'required' => true,
                'choices' => $registry->choices(),
                'default_value' => CodeEventTrophyTrigger::TYPE,
                'return_format' => 'value',
            ],
        ];
        array_push($fields, ...$registry->fields());
        array_push(
            $fields,
            [
                'key' => 'field_trophee_notification_enabled',
                'label' => __('Créer une notification', 'chassesautresor-com'),
                'name' => 'trophee_notification_enabled',
                'type' => 'true_false',
                'default_value' => true,
                'ui' => true,
            ],
            [
                'key' => 'field_trophee_notification_title',
                'label' => __('Titre de la notification', 'chassesautresor-com'),
                'name' => 'trophee_notification_title',
                'type' => 'text',
                'instructions' => __(
                    'Laissez vide pour reprendre le titre du trophée.',
                    'chassesautresor-com'
                ),
                'maxlength' => 190,
                'conditional_logic' => self::notificationCondition(),
            ],
            [
                'key' => 'field_trophee_notification_body',
                'label' => __('Texte de la notification', 'chassesautresor-com'),
                'name' => 'trophee_notification_body',
                'type' => 'textarea',
                'instructions' => __(
                    'Laissez vide pour reprendre le texte court du trophée.',
                    'chassesautresor-com'
                ),
                'rows' => 3,
                'maxlength' => 2000,
                'conditional_logic' => self::notificationCondition(),
            ],
            [
                'key' => 'field_trophee_notification_priority',
                'label' => __('Priorité', 'chassesautresor-com'),
                'name' => 'trophee_notification_priority',
                'type' => 'select',
                'choices' => [
                    '50' => __('Normale', 'chassesautresor-com'),
                    '100' => __('Prioritaire', 'chassesautresor-com'),
                ],
                'default_value' => '100',
                'conditional_logic' => self::notificationCondition(),
            ],
            [
                'key' => 'field_trophee_notification_channel',
                'label' => __('Affichage', 'chassesautresor-com'),
                'name' => 'trophee_notification_channel',
                'type' => 'select',
                'choices' => [
                    'center' => __('Centre de notifications', 'chassesautresor-com'),
                    'overlay' => __('Overlay uniquement', 'chassesautresor-com'),
                    'both' => __('Centre et overlay', 'chassesautresor-com'),
                ],
                'default_value' => 'both',
                'conditional_logic' => self::notificationCondition(),
            ]
        );

        return $fields;
    }

    /** @return array<int, array<int, array<string, string>>> */
    private static function notificationCondition(): array
    {
        return [
            [
                [
                    'field' => 'field_trophee_notification_enabled',
                    'operator' => '==',
                    'value' => '1',
                ],
            ],
        ];
    }
}
