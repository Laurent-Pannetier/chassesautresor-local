<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Build the policy and point context used by manual-answer forms. */
final class RiddleAnswerContextService {
    public function canAnswer(int $userId, int $riddleId): bool {
        if ($userId <= 0 || $riddleId <= 0) {
            return false;
        }

        return in_array(
            enigme_get_statut_utilisateur($riddleId, $userId),
            ['en_cours', 'echouee', 'abandonnee'],
            true
        );
    }

    /** @return array<string,int|string> */
    public function points(int $userId, int $riddleId): array {
        $cost = (int) get_field('enigme_tentative_cout_points', $riddleId);
        $balance = get_user_points($userId);
        $missing = max(0, $cost - $balance);
        $label = $missing <= 0 && $cost > 0
            ? sprintf(__('Valider — %d pts', 'chassesautresor-com'), $cost)
            : __('Valider', 'chassesautresor-com');

        return [
            'cout' => $cost,
            'boutique_url' => home_url('/boutique/'),
            'disabled' => $missing > 0 ? 'disabled' : '',
            'points_manquants' => $missing,
            'solde_avant' => $balance,
            'solde_apres' => $balance - $cost,
            'seuil' => (int) get_option('enigme_cout_eleve', 300),
            'label_btn' => $label,
        ];
    }
}
