<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Presentation;

use ChassesAuTresor\Core\Progress\RiddleAnswerContextService;

/** Render answer controls that do not rely on callbacks or markup from the historical theme. */
final class PortableRiddleAnswerRenderer {
    private RiddleAnswerContextService $context;

    public function __construct(?RiddleAnswerContextService $context = null) {
        $this->context = $context ?? new RiddleAnswerContextService();
    }

    public function render(int $riddleId, int $userId): string {
        $mode = $this->validationMode((string) get_field('enigme_mode_validation', $riddleId));
        if ($mode === 'aucune') {
            return '';
        }
        if (!$this->context->canAnswer($userId, $riddleId)) {
            return '<p>' . esc_html__('Vous ne pouvez plus répondre à cette énigme.', 'chassesautresor-com') . '</p>';
        }

        $points = $this->context->points($userId, $riddleId);
        if ((int) $points['points_manquants'] > 0) {
            return '<p>' . esc_html(sprintf(
                __('Il vous manque %d points pour soumettre votre réponse.', 'chassesautresor-com'),
                (int) $points['points_manquants']
            )) . '</p><a class="cat-core-button" href="' . esc_url((string) $points['boutique_url']) . '">'
                . esc_html__('Ajouter des points', 'chassesautresor-com') . '</a>';
        }

        $automatic = $mode === 'automatique';
        $formClass = $automatic ? 'formulaire-reponse-automatique' : 'formulaire-reponse-manuelle';
        $answerName = $automatic ? 'reponse' : 'reponse_manuelle';
        $nonceName = $automatic ? 'nonce' : 'reponse_manuelle_nonce';
        $nonceAction = $automatic ? 'reponse_auto_nonce' : 'reponse_manuelle_nonce';

        return '<form method="post" class="cat-core-answer-form ' . esc_attr($formClass) . '">'
            . '<label for="cat-core-answer-' . $riddleId . '">'
            . esc_html__('Votre réponse', 'chassesautresor-com') . '</label>'
            . '<textarea id="cat-core-answer-' . $riddleId . '" name="' . esc_attr($answerName)
            . '" required></textarea>'
            . '<input type="hidden" name="enigme_id" value="' . esc_attr((string) $riddleId) . '">'
            . '<input type="hidden" name="' . esc_attr($nonceName) . '" value="'
            . esc_attr(wp_create_nonce($nonceAction)) . '">'
            . '<button type="submit">' . esc_html((string) $points['label_btn']) . '</button>'
            . '</form><div class="cat-core-feedback" role="status" aria-live="polite"></div>';
    }

    private function validationMode(string $mode): string {
        $mode = strtolower(trim($mode));
        if (in_array($mode, ['automatique', 'manuelle', 'aucune'], true)) {
            return $mode;
        }

        return 'automatique';
    }
}
