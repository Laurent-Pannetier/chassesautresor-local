<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * WordPress AJAX adapter for individual hint field mutations.
 */
class HintFieldMutationAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_modifier_champ_indice', [self::class, 'handle'], 10, 1);
    }

    public static function handle(): void {
        check_ajax_referer('hint_management', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }

        $field = sanitize_text_field($_POST['champ'] ?? '');
        $value = $_POST['valeur'] ?? '';
        $hintId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($field === '' || $hintId <= 0 || get_post_type($hintId) !== 'indice') {
            wp_send_json_error('⚠️ donnees_invalides');
        }
        if (!utilisateur_peut_modifier_post($hintId)
            || !(new WordPressContentAccessResolver())->canEditFields($hintId)
        ) {
            wp_send_json_error('⚠️ acces_refuse');
        }

        $mutation = (new HintFieldMutationService())->apply(
            $hintId,
            $field,
            $value,
            'sanitize_text_field',
            'wp_kses_post',
            static fn (string $date) => HuntDateMutationAjaxHandler::parseDate(
                $date,
                ['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i']
            ),
            static fn (array $postData) => wp_update_post($postData, true),
            'update_field',
            'is_wp_error'
        );
        if ($mutation['error'] !== null) {
            wp_send_json_error('⚠️ ' . $mutation['error']);
        }
        if ($mutation['refresh_cache']) {
            do_action('chassesautresor_hint_cache_refresh_requested', $hintId);
        }

        wp_send_json_success(['champ' => $field, 'valeur' => $value]);
    }
}
