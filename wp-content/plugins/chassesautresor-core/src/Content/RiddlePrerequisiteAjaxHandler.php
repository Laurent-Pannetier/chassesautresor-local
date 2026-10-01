<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** AJAX transport for selecting prerequisite-based riddle access. */
class RiddlePrerequisiteAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_verifier_et_enregistrer_condition_pre_requis', [self::class, 'handle']);
    }

    public static function handle(): void {
        $authenticated = is_user_logged_in();
        $userId = $authenticated ? (int) get_current_user_id() : 0;
        $riddleId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $isRiddle = $authenticated && $riddleId > 0 && get_post_type($riddleId) === 'enigme';
        $prerequisites = $isRiddle ? get_field('enigme_acces_pre_requis', $riddleId) : [];
        $error = (new RiddlePrerequisiteService())->getConditionUpdateError(
            $authenticated,
            $isRiddle,
            $isRiddle && (int) get_post_field('post_author', $riddleId) === $userId,
            is_array($prerequisites) ? $prerequisites : []
        );
        if ($error !== null) {
            $messages = [
                RiddlePrerequisiteService::ERROR_UNAUTHENTICATED =>
                    __('Utilisateur non connecté.', 'chassesautresor-com'),
                RiddlePrerequisiteService::ERROR_INVALID_RIDDLE =>
                    __('Identifiant ou type de contenu invalide.', 'chassesautresor-com'),
                RiddlePrerequisiteService::ERROR_FORBIDDEN => __('Accès refusé.', 'chassesautresor-com'),
                RiddlePrerequisiteService::ERROR_MISSING_PREREQUISITES =>
                    __('Aucun prérequis sélectionné.', 'chassesautresor-com'),
            ];
            wp_send_json_error($messages[$error]);
        }

        update_field('enigme_acces_condition', 'pre_requis', $riddleId);
        wp_send_json_success(__('Condition « prérequis » enregistrée.', 'chassesautresor-com'));
    }
}
