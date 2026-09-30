<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * WordPress AJAX adapter for solution creation and edition modals.
 */
class SolutionModalAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_creer_solution_modal', [self::class, 'create'], 10, 1);
        $addAction('wp_ajax_modifier_solution_modal', [self::class, 'update'], 10, 1);
    }

    public static function create(): void {
        check_ajax_referer('solution_management', 'nonce');
        $fieldPolicy = new SolutionFieldPolicyService();
        $isAuthenticated = is_user_logged_in();
        [$targetId, $targetType, $hasValidTarget] = self::getTarget($isAuthenticated);
        $linkedRiddleId = isset($_POST['solution_enigme_linked'])
            ? (int) $_POST['solution_enigme_linked']
            : 0;
        $hasConsistentTarget = $hasValidTarget
            && $fieldPolicy->hasConsistentRiddleTarget($targetType, $targetId, $linkedRiddleId);
        $isAuthorized = $hasConsistentTarget
            && apply_filters(
                'chassesautresor_can_manage_solution',
                false,
                'create',
                $targetType,
                $targetId
            );
        $rawExplanation = (string) ($_POST['solution_explication'] ?? '');
        $requestError = (new SolutionModalPolicyService())->getCreationError(
            $isAuthenticated,
            $hasValidTarget,
            $hasConsistentTarget,
            $isAuthorized,
            $fieldPolicy->hasRequiredContent(self::hasFile(), $rawExplanation)
        );
        if ($requestError !== null) {
            wp_send_json_error($requestError);
        }

        $solutionId = apply_filters('chassesautresor_create_solution', 0, $targetId, $targetType);
        if (is_wp_error($solutionId)) {
            wp_send_json_error($solutionId->get_error_message());
        }
        $solutionId = (int) $solutionId;
        if ($solutionId <= 0) {
            wp_send_json_error('echec_creation');
        }

        self::applyMutation($solutionId, false, $rawExplanation, $fieldPolicy);
        wp_send_json_success(['solution_id' => $solutionId]);
    }

    public static function update(): void {
        check_ajax_referer('solution_management', 'nonce');
        $fieldPolicy = new SolutionFieldPolicyService();
        $isAuthenticated = is_user_logged_in();
        $solutionId = $isAuthenticated && isset($_POST['solution_id']) ? (int) $_POST['solution_id'] : 0;
        $hasValidSolution = $solutionId > 0 && get_post_type($solutionId) === 'solution';
        [$targetId, $targetType, $hasValidTarget] = self::getTarget($hasValidSolution);
        $isAuthorized = $hasValidTarget
            && apply_filters(
                'chassesautresor_can_manage_solution',
                false,
                'edit',
                $targetType,
                $targetId
            );
        $rawExplanation = (string) ($_POST['solution_explication'] ?? '');
        $requestError = (new SolutionModalPolicyService())->getEditionError(
            $isAuthenticated,
            $hasValidSolution,
            $hasValidTarget,
            $isAuthorized,
            $fieldPolicy->hasRequiredContent(self::hasFile(), $rawExplanation)
        );
        if ($requestError !== null) {
            wp_send_json_error($requestError);
        }

        self::applyMutation($solutionId, true, $rawExplanation, $fieldPolicy);
        wp_send_json_success(['solution_id' => $solutionId]);
    }

    /** @return array{0:int,1:string,2:bool} */
    private static function getTarget(bool $canReadRequest): array {
        $targetId = $canReadRequest && isset($_POST['objet_id']) ? (int) $_POST['objet_id'] : 0;
        $targetType = $canReadRequest ? sanitize_key($_POST['objet_type'] ?? '') : '';
        $isValid = $targetId > 0
            && in_array($targetType, ['chasse', 'enigme'], true)
            && get_post_type($targetId) === $targetType;

        return [$targetId, $targetType, $isValid];
    }

    private static function hasFile(): bool {
        return !empty($_FILES['solution_fichier']['tmp_name']) || !empty($_POST['solution_fichier']);
    }

    private static function applyMutation(
        int $solutionId,
        bool $isEdition,
        string $rawExplanation,
        SolutionFieldPolicyService $fieldPolicy
    ): void {
        $fileInput = (new SolutionFileInputService())->resolve(
            $solutionId,
            isset($_FILES['solution_fichier']) ? (array) $_FILES['solution_fichier'] : [],
            isset($_POST['solution_fichier']),
            isset($_POST['solution_fichier']) ? (int) $_POST['solution_fichier'] : 0,
            static function (int $postId) {
                if (!function_exists('media_handle_upload')) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    require_once ABSPATH . 'wp-admin/includes/media.php';
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                }

                return media_handle_upload('solution_fichier', $postId);
            },
            'is_wp_error',
            static fn ($error): string => $error->get_error_message()
        );
        if ($fileInput['error'] !== null) {
            wp_send_json_error($fileInput['error']);
        }

        $schedule = $fieldPolicy->normalizeSchedule(
            sanitize_key($_POST['solution_disponibilite'] ?? 'fin_chasse'),
            isset($_POST['solution_decalage_jours']) ? (int) $_POST['solution_decalage_jours'] : 0,
            sanitize_text_field($_POST['solution_heure_publication'] ?? '')
        );
        SolutionMutationService::apply(
            $solutionId,
            $fileInput['id'],
            $isEdition && $fileInput['submitted'],
            wp_kses_post($rawExplanation),
            $schedule,
            $isEdition
        );
    }
}
