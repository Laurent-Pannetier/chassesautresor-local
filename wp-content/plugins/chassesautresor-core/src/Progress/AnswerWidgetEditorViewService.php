<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Describe widget editor controls independently from the step template. */
final class AnswerWidgetEditorViewService {
    /** @return array<int,array<string,mixed>> */
    public function widgets(): array {
        return [
            [
                'type' => 'click',
                'label' => __('Simple clic', 'chassesautresor-com'),
                'fields' => [
                    $this->field(
                        'text',
                        'button_label',
                        __('Libellé du bouton', 'chassesautresor-com'),
                        ['maxlength' => 80, 'default' => __('Continuer', 'chassesautresor-com')]
                    ),
                ],
            ],
            [
                'type' => 'text',
                'label' => __('Réponse texte', 'chassesautresor-com'),
                'fields' => [
                    $this->field(
                        'textarea',
                        'accepted_answers',
                        __('Réponses acceptées — une par ligne', 'chassesautresor-com'),
                        ['rows' => 4]
                    ),
                    $this->field(
                        'checkbox',
                        'case_sensitive',
                        __('Respecter les majuscules et minuscules', 'chassesautresor-com')
                    ),
                    $this->field(
                        'textarea',
                        'variants',
                        __('Variantes personnalisées — réponse | message', 'chassesautresor-com'),
                        [
                            'rows' => 4,
                            'help' => __(
                                'Une variante affiche un message d’aide sans consommer de tentative.',
                                'chassesautresor-com'
                            ),
                        ]
                    ),
                ],
            ],
            [
                'type' => 'directions',
                'label' => __('Pavé à huit directions', 'chassesautresor-com'),
                'fields' => [
                    $this->field(
                        'textarea',
                        'direction_sequences',
                        __('Codes acceptés — un par ligne', 'chassesautresor-com'),
                        [
                            'rows' => 4,
                            'help' => __('Exemple : N, NE, E, SE, S, SO, O, NO', 'chassesautresor-com'),
                        ]
                    ),
                ],
            ],
            [
                'type' => 'colors',
                'label' => __('Clavier de couleurs', 'chassesautresor-com'),
                'fields' => [
                    $this->field(
                        'textarea',
                        'color_sequences',
                        __('Codes acceptés — un par ligne', 'chassesautresor-com'),
                        [
                            'rows' => 4,
                            'help' => __(
                                'Couleurs : red, orange, yellow, green, blue, purple, indigo, pink, brown, '
                                    . 'grey, black, white.',
                                'chassesautresor-com'
                            ),
                        ]
                    ),
                ],
            ],
            [
                'type' => 'numbers',
                'label' => __('Pavé numérique', 'chassesautresor-com'),
                'fields' => [
                    $this->field(
                        'textarea',
                        'number_sequences',
                        __('Codes acceptés — un par ligne', 'chassesautresor-com'),
                        [
                            'rows' => 4,
                            'help' => __('Les zéros initiaux sont conservés. Exemple : 0129.', 'chassesautresor-com'),
                        ]
                    ),
                ],
            ],
            [
                'type' => 'safe_dial',
                'label' => __('Molette de coffre-fort', 'chassesautresor-com'),
                'fields' => [
                    $this->field(
                        'textarea',
                        'safe_dial_sequences',
                        __('Codes acceptés — un par ligne', 'chassesautresor-com'),
                        [
                            'rows' => 4,
                            'help' => __(
                                'H signifie horaire et A antihoraire. Exemple : H11 A51.',
                                'chassesautresor-com'
                            ),
                        ]
                    ),
                ],
            ],
            [
                'type' => 'piano',
                'label' => __('Piano', 'chassesautresor-com'),
                'fields' => [
                    $this->field(
                        'textarea',
                        'piano_sequences',
                        __('Séquences musicales acceptées — une par ligne', 'chassesautresor-com'),
                        [
                            'rows' => 4,
                            'help' => __('Notes de C1 à B2. Exemple : F1 F#2 B2 C1.', 'chassesautresor-com'),
                        ]
                    ),
                ],
            ],
            [
                'type' => 'gps',
                'label' => __('Coordonnées GPS', 'chassesautresor-com'),
                'fields' => [
                    $this->field(
                        'text',
                        'gps_coordinates',
                        __('Coordonnées attendues (latitude longitude)', 'chassesautresor-com'),
                        ['help' => __('Exemple : 48.85837 2.29448', 'chassesautresor-com')]
                    ),
                    $this->field(
                        'text',
                        'gps_tolerance',
                        __('Rayon de tolérance en mètres', 'chassesautresor-com'),
                        ['default' => '25']
                    ),
                ],
            ],
        ];
    }

    /** @param array<string,mixed> $options @return array<string,mixed> */
    private function field(string $control, string $name, string $label, array $options = []): array {
        return array_merge(['control' => $control, 'name' => $name, 'label' => $label], $options);
    }
}
