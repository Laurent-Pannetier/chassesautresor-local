<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use Throwable;

/**
 * Own the AJAX transport and orchestration for manual and automatic answers.
 */
class RiddleAnswerSubmissionAjaxHandler {
    public static function register(callable $addAction): void
    {
        $addAction('wp_ajax_soumettre_reponse_manuelle', [self::class, 'submitManual']);
        $addAction('wp_ajax_nopriv_soumettre_reponse_manuelle', [self::class, 'submitManual']);
        $addAction('wp_ajax_soumettre_reponse_automatique', [self::class, 'submitAutomatic']);
        $addAction('wp_ajax_nopriv_soumettre_reponse_automatique', [self::class, 'submitAutomatic']);
    }

    public static function submitManual(): void
    {
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $answer = isset($_POST['reponse_manuelle'])
            ? sanitize_textarea_field(wp_unslash((string) $_POST['reponse_manuelle']))
            : '';
        $nonce = isset($_POST['reponse_manuelle_nonce']) ? (string) $_POST['reponse_manuelle_nonce'] : '';
        $userId = (int) get_current_user_id();
        $error = self::validate($userId, $riddleId, $answer, $nonce, 'reponse_manuelle_nonce', false);
        if ($error !== null) {
            wp_send_json_error($error);
        }

        $lockKey = self::acquireLock($riddleId, $userId);
        try {
            $cost = (int) get_field('enigme_tentative_cout_points', $riddleId);
            if ($cost > 0) {
                $reason = sprintf(
                    __("Tentative de réponse pour l'énigme #%d", 'chassesautresor-com'),
                    $riddleId
                );
                deduire_points_utilisateur($userId, $cost, $reason, 'tentative', $riddleId);
            }

            $uid = inserer_tentative($userId, $riddleId, $answer, 'attente', $cost);
            $attemptId = get_last_tentative_insert_id();
            enigme_mettre_a_jour_statut_utilisateur($riddleId, $userId, 'soumis', true);
            $link = '<a href="' . esc_url(get_permalink($riddleId)) . '">'
                . esc_html(get_the_title($riddleId)) . '</a>';
            myaccount_add_persistent_message($userId, 'tentative_' . $uid, $link, 'info');
            envoyer_mail_reponse_manuelle($userId, $riddleId, $answer, $uid);
            $timestamp = current_time('timestamp');
        } catch (Throwable $exception) {
            self::releaseLock($lockKey);
            cat_debug('Erreur tentative : ' . $exception->getMessage());
            wp_send_json_error('erreur_interne');
        }
        self::releaseLock($lockKey);

        wp_send_json_success([
            'uid' => $uid,
            'id' => $attemptId,
            'date' => wp_date('d/m/Y', $timestamp),
            'time' => wp_date('H:i', $timestamp),
            'points' => get_user_points($userId),
        ]);
    }

    public static function submitAutomatic(): void
    {
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $answer = isset($_POST['reponse']) ? sanitize_text_field(wp_unslash((string) $_POST['reponse'])) : '';
        $nonce = isset($_POST['nonce']) ? (string) $_POST['nonce'] : '';
        $userId = (int) get_current_user_id();
        $error = self::validate($userId, $riddleId, $answer, $nonce, 'reponse_auto_nonce', true);
        if ($error !== null) {
            wp_send_json_error($error);
        }

        $variants = [];
        for ($index = 1; $index <= 4; $index++) {
            $variants[$index] = [
                'texte' => (string) get_field("texte_{$index}", $riddleId),
                'message' => (string) get_field("message_{$index}", $riddleId),
                'casse' => (int) get_field("respecter_casse_{$index}", $riddleId) === 1,
            ];
        }
        $evaluation = (new RiddleAnswerEvaluationService())->evaluate(
            $answer,
            (new RiddleAnswerService())->get($riddleId),
            (int) get_field('enigme_reponse_casse', $riddleId) === 1,
            $variants
        );

        $lockKey = self::acquireLock($riddleId, $userId);
        try {
            $uid = traiter_tentative(
                $userId,
                $riddleId,
                $answer,
                $evaluation['resultat'],
                true,
                false,
                false
            );
        } catch (Throwable $exception) {
            self::releaseLock($lockKey);
            cat_debug('Erreur tentative : ' . $exception->getMessage());
            wp_send_json_error('erreur_interne');
        }
        self::releaseLock($lockKey);

        wp_send_json_success([
            'resultat' => $evaluation['resultat'],
            'message' => $evaluation['message'],
            'uid' => $uid,
            'compteur' => compter_tentatives_du_jour($userId, $riddleId),
            'points' => get_user_points($userId),
        ]);
    }

    private static function validate(
        int $userId,
        int $riddleId,
        string $answer,
        string $nonce,
        string $nonceAction,
        bool $automatic
    ): ?string {
        $loggedIn = is_user_logged_in();
        $postType = $riddleId > 0 ? (string) get_post_type($riddleId) : '';
        $nonceValid = $nonce !== '' && wp_verify_nonce($nonce, $nonceAction) !== false;
        $state = $riddleId > 0 && function_exists('enigme_get_etat_systeme')
            ? (string) enigme_get_etat_systeme($riddleId)
            : '';
        $status = $userId > 0 && $riddleId > 0 && function_exists('enigme_get_statut_utilisateur')
            ? (string) enigme_get_statut_utilisateur($riddleId, $userId)
            : '';
        $attempts = $userId > 0 && $riddleId > 0 && function_exists('compter_tentatives_du_jour')
            ? compter_tentatives_du_jour($userId, $riddleId)
            : 0;

        return (new RiddleAnswerSubmissionPolicy())->validate(
            $loggedIn,
            $nonceValid,
            $riddleId,
            $postType,
            $answer,
            $state,
            $status,
            (int) get_field('enigme_tentative_max', $riddleId),
            $attempts,
            (int) get_field('enigme_tentative_cout_points', $riddleId),
            $userId > 0 && function_exists('get_user_points') ? (int) get_user_points($userId) : 0,
            $automatic
        );
    }

    private static function acquireLock(int $riddleId, int $userId): string
    {
        $lockKey = "enigme_lock_{$riddleId}_{$userId}";
        if (!wp_cache_add($lockKey, 1, 'enigme', 15)) {
            wp_send_json_error('doublon');
        }

        return $lockKey;
    }

    private static function releaseLock(string $lockKey): void
    {
        wp_cache_delete($lockKey, 'enigme');
    }
}
