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
                            'help' => __('Couleurs : red, orange, yellow, green, blue, purple.', 'chassesautresor-com'),
                        ]
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
