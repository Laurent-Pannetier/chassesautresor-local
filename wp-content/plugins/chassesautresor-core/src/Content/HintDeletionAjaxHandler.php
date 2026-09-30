<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * WordPress AJAX adapter for permanent hint deletion.
 */
class HintDeletionAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_supprimer_indice', [self::class, 'handle'], 10, 1);
    }

    public static function handle(): void {
        check_ajax_referer('hint_management', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }

        $hintId = isset($_POST['indice_id']) ? (int) $_POST['indice_id'] : 0;
        if ($hintId <= 0 || get_post_type($hintId) !== 'indice') {
            wp_send_json_error('id_invalide');
        }

        $service = new HintDeletionService();
        $context = $service->resolveContext(
            (string) get_field('indice_cible_type', $hintId),
            get_field('indice_chasse_linked', $hintId),
            get_field('indice_enigme_linked', $hintId),
            static function (int $riddleId): ?int {
                return (new RelationshipService())->normalizeId(
                    get_field('enigme_chasse_associee', $riddleId)
                );
            }
        );
        if ($context === null
            || !apply_filters(
                'chassesautresor_can_manage_hint',
                false,
                'delete',
                $context['target_type'],
                $context['target_id']
            )
        ) {
            wp_send_json_error('acces_refuse');
        }
        if (!$service->delete($hintId)) {
            wp_send_json_error('echec_suppression');
        }

        foreach ($context['reorder_targets'] as $target) {
            do_action('chassesautresor_hint_reorder_requested', $target['id'], $target['type']);
        }
        wp_send_json_success();
    }
}
