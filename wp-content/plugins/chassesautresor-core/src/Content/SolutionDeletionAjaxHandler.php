<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * WordPress AJAX adapter for permanent solution deletion.
 */
class SolutionDeletionAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_supprimer_solution', [self::class, 'handle'], 10, 1);
    }

    public static function handle(): void {
        check_ajax_referer('solution_management', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }

        $solutionId = isset($_POST['solution_id']) ? (int) $_POST['solution_id'] : 0;
        if ($solutionId <= 0 || get_post_type($solutionId) !== 'solution') {
            wp_send_json_error('id_invalide');
        }

        $service = new SolutionDeletionService();
        $target = $service->resolveTarget(
            (string) get_field('solution_cible_type', $solutionId),
            get_field('solution_chasse_linked', $solutionId),
            get_field('solution_enigme_linked', $solutionId)
        );
        if ($target === null || !(new RelatedContentAccessResolver())->canPerform(
            'delete',
            $target['type'],
            $target['id']
        )) {
            wp_send_json_error('acces_refuse');
        }

        if (!$service->delete($solutionId)) {
            wp_send_json_error('echec_suppression');
        }

        wp_send_json_success();
    }
}
