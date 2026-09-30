<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * WordPress AJAX adapter for individual hunt field mutations.
 */
class HuntFieldMutationAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_modifier_champ_chasse', [self::class, 'handle'], 10, 1);
    }

    public static function handle(): void {
        check_ajax_referer('hunt_field_management', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }

        $field = sanitize_text_field($_POST['champ'] ?? '');
        $value = wp_kses_post($_POST['valeur'] ?? '');
        $huntId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($field === '' || !isset($_POST['valeur'])) {
            wp_send_json_error('⚠️ donnees_invalides');
        }
        if ($huntId <= 0 || get_post_type($huntId) !== 'chasse') {
            wp_send_json_error('⚠️ post_invalide');
        }
        if (!apply_filters('chassesautresor_can_modify_hunt', false, $huntId)) {
            wp_send_json_error('⚠️ acces_refuse');
        }

        $requiresFullAccess = !self::isClosureField($field, $value)
            && $field !== 'chasse_principale_liens';
        if ($requiresFullAccess
            && !apply_filters('chassesautresor_can_edit_hunt_fields', false, $huntId)
        ) {
            wp_send_json_error('⚠️ acces_refuse');
        }

        if ($field === 'post_title') {
            $updated = wp_update_post(['ID' => $huntId, 'post_title' => $value], true);
            if (is_wp_error($updated)) {
                wp_send_json_error('⚠️ echec_update_post_title');
            }
            wp_send_json_success(['champ' => $field, 'valeur' => $value]);
        }

        if ($field === 'chasse_principale_liens') {
            self::handleLinks($huntId, $field, (string) $value);
        }

        $handled = false;
        $recalculateStatus = false;
        $reward = (new HuntRewardMutationService())->apply($huntId, $field, $value, 'update_field');
        self::sendMutationError($reward['error']);
        if ($reward['handled']) {
            $handled = true;
            $recalculateStatus = $reward['recalculate_status'];
        }

        $mutation = (new HuntFieldMutationService())->apply(
            $huntId,
            $field,
            $value,
            [HuntDateMutationAjaxHandler::class, 'parseDate'],
            'sanitize_text_field',
            'update_field'
        );
        self::sendMutationError($mutation['error']);
        if ($mutation['handled']) {
            $handled = true;
            $recalculateStatus = $recalculateStatus || $mutation['recalculate_status'];
        }

        $closure = apply_filters(
            'chassesautresor_apply_hunt_closure',
            ['handled' => false, 'error' => null],
            $huntId,
            $field,
            $value
        );
        self::sendMutationError(is_array($closure) ? ($closure['error'] ?? null) : 'echec_mise_a_jour');
        $handled = $handled || (is_array($closure) && !empty($closure['handled']));

        if (!$handled) {
            wp_send_json_error(__('⚠️ champ_non_autorise', 'chassesautresor-com'));
        }
        if ($recalculateStatus || self::triggersStatusUpdate($field)) {
            wp_cache_delete($huntId, 'post');
            sleep(1);
            do_action('chassesautresor_hunt_fields_updated', $huntId);
        }

        wp_send_json_success(['champ' => $field, 'valeur' => $value]);
    }

    private static function handleLinks(int $huntId, string $field, string $value): void {
        $mutation = (new HuntLinkMutationService())->apply(
            $huntId,
            $value,
            'sanitize_text_field',
            'esc_url_raw',
            'get_field',
            'update_field'
        );
        if ($mutation['error'] !== null) {
            $message = $mutation['error'] === 'format_invalide'
                ? __('⚠️ format_invalide', 'chassesautresor-com')
                : __('⚠️ echec_mise_a_jour_liens', 'chassesautresor-com');
            wp_send_json_error($message);
        }
        wp_send_json_success(['champ' => $field, 'valeur' => $mutation['value']]);
    }

    private static function sendMutationError(?string $error): void {
        if ($error === null) {
            return;
        }
        $messages = [
            'format_date_invalide' => __('⚠️ format_date_invalide', 'chassesautresor-com'),
            'valeur_invalide' => __('⚠️ valeur_invalide', 'chassesautresor-com'),
            'echec_mise_a_jour' => __('⚠️ echec_mise_a_jour', 'chassesautresor-com'),
        ];
        wp_send_json_error($messages[$error] ?? $error);
    }

    private static function isClosureField(string $field, $value): bool {
        return ($field === 'champs_caches.chasse_cache_statut' && $value === 'termine')
            || in_array($field, [
                'champs_caches.chasse_cache_gagnants',
                'champs_caches.chasse_cache_date_decouverte',
            ], true);
    }

    private static function triggersStatusUpdate(string $field): bool {
        return in_array($field, [
            'caracteristiques.chasse_infos_date_debut',
            'caracteristiques.chasse_infos_date_fin',
            'caracteristiques.chasse_infos_cout_points',
            'caracteristiques.chasse_infos_duree_illimitee',
            'champs_caches.chasse_cache_statut_validation',
            'chasse_cache_statut_validation',
            'champs_caches.chasse_cache_date_decouverte',
            'chasse_cache_date_decouverte',
        ], true);
    }
}
