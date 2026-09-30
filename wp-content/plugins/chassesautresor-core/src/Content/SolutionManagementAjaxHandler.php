<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * WordPress AJAX adapter for solution management reads.
 */
class SolutionManagementAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_solutions_lister_table', [self::class, 'listTable'], 10, 1);
        $addAction('wp_ajax_chasse_solution_status', [self::class, 'getHuntStatus'], 10, 1);
    }

    public static function listTable(): void {
        self::guardRequest();
        $targetId = isset($_POST['objet_id']) ? (int) $_POST['objet_id'] : 0;
        $targetType = sanitize_key($_POST['objet_type'] ?? '');
        if (!self::isValidTarget($targetId, $targetType)) {
            wp_send_json_error('post_invalide');
        }
        if (!apply_filters(
            'chassesautresor_can_manage_solution',
            false,
            'edit',
            $targetType,
            $targetId
        )) {
            wp_send_json_error('acces_refuse');
        }

        $managementService = new SolutionManagementService();
        $page = $managementService->normalizePage((int) ($_POST['page'] ?? 1), 0);
        $riddleIds = $targetType === 'chasse'
            ? (array) apply_filters('chassesautresor_hunt_riddle_ids', [], $targetId)
            : [];
        $queryArgs = (new SolutionQueryService())->getManagementQueryArgs(
            $targetId,
            $targetType,
            $riddleIds,
            $page,
            5
        );
        $query = new \WP_Query($queryArgs);
        $totalPages = (int) $query->max_num_pages;
        $normalizedPage = $managementService->normalizePage($page, $totalPages);
        if ($normalizedPage !== $page) {
            $page = $normalizedPage;
            $queryArgs['paged'] = $page;
            $query = new \WP_Query($queryArgs);
            $totalPages = (int) $query->max_num_pages;
        }

        $html = (string) apply_filters(
            'chassesautresor_render_solutions_table',
            '',
            $query->posts,
            $page,
            $totalPages,
            $targetType,
            $targetId
        );
        wp_send_json_success(['html' => $html, 'page' => $page, 'pages' => $totalPages]);
    }

    public static function getHuntStatus(): void {
        self::guardRequest();
        $huntId = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        if ($huntId <= 0 || get_post_type($huntId) !== 'chasse') {
            wp_send_json_error('post_invalide');
        }
        if ($riddleId > 0 && get_post_type($riddleId) !== 'enigme') {
            $riddleId = 0;
        }
        if (!apply_filters('chassesautresor_can_manage_solution', false, 'create', 'chasse', $huntId)) {
            wp_send_json_error('acces_refuse');
        }

        $hasHuntSolution = self::solutionExists($huntId, 'chasse');
        $hasRiddleSolution = $riddleId > 0 && self::solutionExists($riddleId, 'enigme');
        $riddles = (array) apply_filters('chassesautresor_hunt_riddles', [], $huntId);
        $riddlesWithoutSolution = array_filter(
            $riddles,
            static fn ($riddle): bool => !self::solutionExists((int) $riddle->ID, 'enigme')
        );
        $riddleIds = array_map(static fn ($riddle): int => (int) $riddle->ID, $riddles);
        $solutionIds = get_posts((new SolutionQueryService())->getManagementQueryArgs(
            $huntId,
            'chasse',
            $riddleIds,
            1,
            1,
            true
        ));

        wp_send_json_success((new SolutionManagementService())->buildHuntStatus(
            $hasHuntSolution,
            $hasRiddleSolution,
            count($riddles),
            count($riddlesWithoutSolution),
            is_array($solutionIds) ? count($solutionIds) : 0
        ));
    }

    private static function guardRequest(): void {
        check_ajax_referer('solution_management', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }
    }

    private static function isValidTarget(int $targetId, string $targetType): bool {
        return $targetId > 0
            && in_array($targetType, ['chasse', 'enigme'], true)
            && get_post_type($targetId) === $targetType;
    }

    private static function solutionExists(int $targetId, string $targetType): bool {
        return (bool) apply_filters(
            'chassesautresor_solution_exists',
            false,
            $targetId,
            $targetType
        );
    }
}
