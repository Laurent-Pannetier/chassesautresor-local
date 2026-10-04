<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Build a target-independent player view model from a widget configuration. */
final class AnswerWidgetPlayerViewService {
    /** @param array<string,mixed> $configuration @return array<string,mixed> */
    public function build(array $configuration, int $maximumFailures = 0, int $usedFailures = 0): array {
        $type = (string) ($configuration['type'] ?? '');
        if ($type === 'click') {
            return [
                'type' => 'click',
                'form_class' => 'riddle-step-click-form',
                'nonce_action' => 'riddle_step_click',
                'action' => 'confirmer_etape_enigme',
                'input_name' => null,
                'button_label' => (string) (($configuration['button_label'] ?? '')
                    ?: __('Continuer', 'chassesautresor-com')),
                'limit_reached' => false,
            ];
        }

        if (in_array($type, ['directions', 'colors', 'numbers', 'safe_dial', 'gps'], true)) {
            return [
                'type' => $type,
                'form_class' => 'riddle-step-text-form riddle-step-' . $type . '-form',
                'nonce_action' => 'riddle_step_answer',
                'action' => 'soumettre_reponse_etape',
                'input_name' => 'reponse',
                'button_label' => __('Valider', 'chassesautresor-com'),
                'limit_reached' => $maximumFailures > 0 && $usedFailures >= $maximumFailures,
            ];
        }

        return [
            'type' => 'text',
            'form_class' => 'riddle-step-text-form',
            'nonce_action' => 'riddle_step_answer',
            'action' => 'soumettre_reponse_etape',
            'input_name' => 'reponse',
            'input_label' => __('Votre réponse', 'chassesautresor-com'),
            'button_label' => __('Valider', 'chassesautresor-com'),
            'limit_reached' => $maximumFailures > 0 && $usedFailures >= $maximumFailures,
        ];
    }
}
