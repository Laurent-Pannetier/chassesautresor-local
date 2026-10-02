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
                'key' => 'field_etape_libelle',
                'label' => __('Nom de l’étape', 'chassesautresor-com'),
                'name' => 'etape_libelle',
                'type' => 'text',
                'instructions' => __("Nom utilisé dans l’interface d’édition.", 'chassesautresor-com'),
                'required' => true,
                'maxlength' => 120,
                'placeholder' => __('Ex. Le symbole de la porte', 'chassesautresor-com'),
            ],
            [
                'key' => 'field_etape_afficher_titre',
                'label' => __('Afficher le titre au joueur', 'chassesautresor-com'),
                'name' => 'etape_afficher_titre',
                'type' => 'true_false',
                'instructions' => __('Affiche le nom de l’étape au-dessus de son contenu.', 'chassesautresor-com'),
                'required' => false,
                'default_value' => false,
                'ui' => true,
                'ui_on_text' => __('Titre visible', 'chassesautresor-com'),
                'ui_off_text' => __('Titre masqué', 'chassesautresor-com'),
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
            [
                'key' => 'field_etape_widget_type',
                'label' => __('Widget de réponse', 'chassesautresor-com'),
                'name' => 'etape_widget_type',
                'type' => 'select',
                'instructions' => __(
                    'Interface utilisée par le joueur pour saisir sa réponse.',
                    'chassesautresor-com'
                ),
                'required' => true,
                'choices' => [
                    'texte' => __('Champ texte', 'chassesautresor-com'),
                    'directions_8' => __('Pavé à huit directions', 'chassesautresor-com'),
                ],
                'default_value' => 'texte',
                'allow_null' => false,
                'multiple' => false,
                'ui' => true,
                'return_format' => 'value',
            ],
            self::textAnswersField(),
            [
                'key' => 'field_etape_reponse_respecter_casse',
                'label' => __('Respecter les majuscules et minuscules', 'chassesautresor-com'),
                'name' => 'etape_reponse_respecter_casse',
                'type' => 'true_false',
                'instructions' => __("Si désactivé, « Trésor » et « trésor » sont identiques.", 'chassesautresor-com'),
                'required' => false,
                'default_value' => false,
                'ui' => true,
                'ui_on_text' => __('Sensible à la casse', 'chassesautresor-com'),
                'ui_off_text' => __('Ignorer la casse', 'chassesautresor-com'),
                'conditional_logic' => self::widgetCondition('texte'),
            ],
            self::directionsField(),
            [
                'key' => 'field_etape_widget_afficher_saisie',
                'label' => __('Afficher la séquence saisie', 'chassesautresor-com'),
                'name' => 'etape_widget_afficher_saisie',
                'type' => 'true_false',
                'instructions' => __(
                    'Affiche les directions déjà sélectionnées avant validation.',
                    'chassesautresor-com'
                ),
                'required' => false,
                'default_value' => true,
                'ui' => true,
                'ui_on_text' => __('Séquence visible', 'chassesautresor-com'),
                'ui_off_text' => __('Séquence masquée', 'chassesautresor-com'),
                'conditional_logic' => self::widgetCondition('directions_8'),
            ],
            [
                'key' => 'field_etape_widget_autoriser_effacement',
                'label' => __('Afficher le bouton Effacer', 'chassesautresor-com'),
                'name' => 'etape_widget_autoriser_effacement',
                'type' => 'true_false',
                'instructions' => __(
                    'Permet de recommencer la séquence avant de la soumettre.',
                    'chassesautresor-com'
                ),
                'required' => false,
                'default_value' => true,
                'ui' => true,
                'ui_on_text' => __('Bouton affiché', 'chassesautresor-com'),
                'ui_off_text' => __('Bouton masqué', 'chassesautresor-com'),
                'conditional_logic' => self::widgetCondition('directions_8'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function textAnswersField(): array {
        return [
            'key' => 'field_etape_reponses_texte',
            'label' => __('Réponses acceptées', 'chassesautresor-com'),
            'name' => 'etape_reponses_texte',
            'type' => 'repeater',
            'instructions' => __('Une seule de ces réponses suffit pour réussir l’étape.', 'chassesautresor-com'),
            'required' => true,
            'min' => 1,
            'max' => 5,
            'layout' => 'table',
            'button_label' => __('Ajouter une réponse acceptée', 'chassesautresor-com'),
            'sub_fields' => [
                [
                    'key' => 'field_etape_reponse_texte_valeur',
                    'label' => __('Réponse', 'chassesautresor-com'),
                    'name' => 'valeur',
                    'type' => 'text',
                    'required' => true,
                    'maxlength' => 255,
                    'placeholder' => __('Réponse attendue', 'chassesautresor-com'),
                ],
            ],
            'conditional_logic' => self::widgetCondition('texte'),
        ];
    }

    /** @return array<string, mixed> */
    private static function directionsField(): array {
        return [
            'key' => 'field_etape_directions_sequence',
            'label' => __('Séquence de directions', 'chassesautresor-com'),
            'name' => 'etape_directions_sequence',
            'type' => 'repeater',
            'instructions' => __('Ordre exact des directions que le joueur doit saisir.', 'chassesautresor-com'),
            'required' => true,
            'min' => 1,
            'max' => 20,
            'layout' => 'table',
            'button_label' => __('Ajouter une direction', 'chassesautresor-com'),
            'sub_fields' => [
                [
                    'key' => 'field_etape_direction_valeur',
                    'label' => __('Direction', 'chassesautresor-com'),
                    'name' => 'direction',
                    'type' => 'select',
                    'required' => true,
                    'choices' => [
                        'N' => __('Haut', 'chassesautresor-com'),
                        'NE' => __('Haut-droite', 'chassesautresor-com'),
                        'E' => __('Droite', 'chassesautresor-com'),
                        'SE' => __('Bas-droite', 'chassesautresor-com'),
                        'S' => __('Bas', 'chassesautresor-com'),
                        'SW' => __('Bas-gauche', 'chassesautresor-com'),
                        'W' => __('Gauche', 'chassesautresor-com'),
                        'NW' => __('Haut-gauche', 'chassesautresor-com'),
                    ],
                    'allow_null' => false,
                    'multiple' => false,
                    'ui' => false,
                    'return_format' => 'value',
                ],
            ],
            'conditional_logic' => self::widgetCondition('directions_8'),
        ];
    }

    /** @return array<int, array<int, array<string, string>>> */
    private static function widgetCondition(string $widgetType): array {
        return [
            [
                [
                    'field' => 'field_etape_widget_type',
                    'operator' => '==',
                    'value' => $widgetType,
                ],
            ],
        ];
    }
}
