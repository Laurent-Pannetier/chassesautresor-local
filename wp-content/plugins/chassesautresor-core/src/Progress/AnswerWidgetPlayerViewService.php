<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Build a target-independent player view model from a widget configuration. */
final class AnswerWidgetPlayerViewService {
    private const INTERACTIVE_TYPES = ['directions', 'colors', 'numbers', 'safe_dial', 'piano', 'gps'];

    /** @param array<string,mixed> $configuration @return array<string,mixed> */
    public function build(array $configuration, int $maximumFailures = 0, int $usedFailures = 0): array {
        $type = (string) ($configuration['type'] ?? '');
        $targetType = (string) ($configuration['target_type'] ?? 'enigme_etape');
        $isFinalAnswer = $targetType === 'enigme';

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

        if (in_array($type, self::INTERACTIVE_TYPES, true)) {
            $typeClass = 'riddle-step-' . $type . '-form';
            return [
                'type' => $type,
                'form_class' => $isFinalAnswer
                    ? 'bloc-reponse formulaire-reponse-auto ' . $typeClass
                    : 'riddle-step-text-form ' . $typeClass,
                'nonce_action' => $isFinalAnswer ? 'reponse_auto_nonce' : 'riddle_step_answer',
                'action' => $isFinalAnswer ? 'soumettre_reponse_automatique' : 'soumettre_reponse_etape',
                'input_name' => 'reponse',
                'button_label' => __('Valider', 'chassesautresor-com'),
                'limit_reached' => $maximumFailures > 0 && $usedFailures >= $maximumFailures,
            ];
        }

        return [
            'type' => 'text',
            'form_class' => $isFinalAnswer
                ? 'bloc-reponse formulaire-reponse-auto'
                : 'riddle-step-text-form',
            'nonce_action' => $isFinalAnswer ? 'reponse_auto_nonce' : 'riddle_step_answer',
            'action' => $isFinalAnswer ? 'soumettre_reponse_automatique' : 'soumettre_reponse_etape',
            'input_name' => 'reponse',
            'input_label' => __('Votre réponse', 'chassesautresor-com'),
            'button_label' => __('Valider', 'chassesautresor-com'),
            'limit_reached' => $maximumFailures > 0 && $usedFailures >= $maximumFailures,
        ];
    }
}
