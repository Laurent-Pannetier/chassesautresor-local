<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Register the ACF schema used to configure intermediate riddle steps. */
final class RiddleStepFieldGroupRegistrar {
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
            'key' => 'group_enigme_etape_configuration',
            'title' => __('Configuration de l’étape', 'chassesautresor-com'),
            'fields' => self::fields(),
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => RiddleStepPostTypeRegistrar::POST_TYPE,
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
                'key' => 'field_etape_enigme_associee',
                'label' => __('Énigme associée', 'chassesautresor-com'),
                'name' => 'etape_enigme_associee',
                'type' => 'post_object',
                'instructions' => __('Énigme à laquelle appartient cette étape.', 'chassesautresor-com'),
                'required' => true,
                'post_type' => ['enigme'],
                'taxonomy' => [],
                'allow_null' => false,
                'multiple' => false,
                'return_format' => 'id',
                'ui' => true,
            ],
            [
                'key' => 'field_etape_contenu',
                'label' => __('Texte de l’étape', 'chassesautresor-com'),
                'name' => 'etape_contenu',
                'type' => 'wysiwyg',
                'instructions' => __(
                    'Contenu présenté au joueur lorsque l’étape est débloquée.',
                    'chassesautresor-com'
                ),
                'required' => false,
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'media_upload' => false,
                'delay' => true,
            ],
            [
                'key' => 'field_etape_image',
                'label' => __('Image de l’étape', 'chassesautresor-com'),
                'name' => 'etape_image',
                'type' => 'image',
                'instructions' => __('Illustration affichée avec le texte de cette étape.', 'chassesautresor-com'),
                'required' => false,
                'return_format' => 'id',
                'preview_size' => 'medium',
                'library' => 'all',
                'mime_types' => 'jpg,jpeg,png,webp,gif',
            ],
        ];
    }
}
