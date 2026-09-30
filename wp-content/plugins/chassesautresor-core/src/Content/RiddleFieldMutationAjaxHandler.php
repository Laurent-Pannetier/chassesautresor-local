<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * WordPress AJAX adapter for individual riddle field mutations.
 */
class RiddleFieldMutationAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_modifier_champ_enigme', [self::class, 'handle'], 10, 1);
    }

    public static function handle(): void {
        check_ajax_referer('modifier_champ_enigme', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }

        $field = sanitize_text_field($_POST['champ'] ?? '');
        $value = $_POST['valeur'] ?? '';
        $riddleId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($field === '' || $riddleId <= 0 || get_post_type($riddleId) !== 'enigme') {
            wp_send_json_error('⚠️ donnees_invalides');
        }
        if (!apply_filters('chassesautresor_can_modify_riddle', false, $riddleId)
            || !apply_filters('chassesautresor_can_edit_riddle_fields', false, $riddleId)
        ) {
            wp_send_json_error('⚠️ acces_refuse');
        }

        $policy = new RiddleFieldPolicyService();
        if (!$policy->isEditableField($field)) {
            wp_send_json_error('⚠️ champ_interdit');
        }
        if ($field === 'enigme_acces_condition'
            && $policy->isForbiddenAccessCondition((string) $value)
        ) {
            wp_send_json_error(
                __('⚠️ Interdit : cette valeur est gérée automatiquement.', 'chassesautresor-com')
            );
        }

        $wasComplete = (bool) get_field('enigme_cache_complet', $riddleId);
        $mutation = (new RiddleMutationService())->apply(
            $riddleId,
            $field,
            $value,
            static fn (string $date) => HuntDateMutationAjaxHandler::parseDate(
                $date,
                ['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i']
            ),
            strtotime(date('Y-m-d'))
        );
        if ($mutation['error'] !== null) {
            wp_send_json_error('⚠️ ' . $mutation['error']);
        }

        if ($mutation['refresh_state']) {
            do_action('chassesautresor_riddle_state_refresh_requested', $riddleId);
        }
        if ($mutation['terminal']) {
            wp_send_json_success(['champ' => $field, 'valeur' => $value]);
        }

        do_action('chassesautresor_riddle_completeness_refresh_requested', $riddleId);
        $isComplete = (bool) get_field('enigme_cache_complet', $riddleId);
        $huntId = (new RelationshipService())->normalizeId(
            get_field('enigme_chasse_associee', $riddleId)
        ) ?? 0;

        wp_send_json_success(
            (new RiddleManagementService())->getFieldUpdateResponse(
                $field,
                $value,
                $wasComplete,
                $isComplete,
                $huntId
            )
        );
    }
}
