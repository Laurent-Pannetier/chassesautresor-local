<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * WordPress AJAX adapter for the paginated hint management table.
 */
class HintTableAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_indices_lister_table', [self::class, 'handle'], 10, 1);
    }

    public static function handle(): void {
        check_ajax_referer('hint_management', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }

        $targetId = isset($_POST['objet_id']) ? (int) $_POST['objet_id'] : 0;
        $targetType = sanitize_key($_POST['objet_type'] ?? '');
        if ($targetId <= 0
            || !in_array($targetType, ['chasse', 'enigme'], true)
            || get_post_type($targetId) !== $targetType
        ) {
            wp_send_json_error('post_invalide');
        }
        if (!apply_filters('chassesautresor_can_manage_hint', false, 'edit', $targetType, $targetId)) {
            wp_send_json_error('acces_refuse');
        }

        $huntId = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        if ($targetType === 'enigme') {
            $riddleId = $targetId;
            if ($huntId <= 0) {
                $huntId = (int) apply_filters('chassesautresor_hint_related_hunt_id', 0, $riddleId);
            }
        } else {
            $huntId = $targetId;
        }

        $perPage = $targetType === 'chasse' ? 5 : 8;
        $riddleIds = $targetType === 'chasse'
            ? (array) apply_filters('chassesautresor_hint_hunt_riddle_ids', [], $targetId)
            : [];
        $queryService = new HintQueryService();
        $ids = get_posts($queryService->getManagementTableQueryArgs(
            $targetId,
            $targetType,
            $riddleIds,
            (int) ($_POST['page'] ?? 1),
            $perPage,
            true
        ));
        $ids = is_array($ids) ? $ids : [];
        $pagination = (new HintManagementService())->paginate(
            (int) ($_POST['page'] ?? 1),
            count($ids),
            $perPage
        );
        $query = new \WP_Query($queryService->getManagementTableQueryArgs(
            $targetId,
            $targetType,
            $riddleIds,
            $pagination['page'],
            $perPage
        ));
        $counts = (new HintManagementService())->countByTargetType(
            $ids,
            static fn (int $hintId): string => (string) get_post_meta(
                $hintId,
                'indice_cible_type',
                true
            )
        );

        $hasRiddleHints = false;
        if ($riddleId > 0) {
            $riddleHintIds = get_posts($queryService->getManagementTableQueryArgs(
                $riddleId,
                'enigme',
                [],
                1,
                1,
                true
            ));
            $hasRiddleHints = is_array($riddleHintIds) && $riddleHintIds !== [];
        }
        $toggle = self::buildToggle($hasRiddleHints, $huntId, $riddleId, $targetType);
        $html = (string) apply_filters(
            'chassesautresor_render_hint_table',
            '',
            $query->posts,
            $pagination['page'],
            $pagination['pages'],
            $targetType,
            $targetId,
            $counts,
            $toggle
        );

        wp_send_json_success([
            'html' => $html,
            'page' => $pagination['page'],
            'pages' => $pagination['pages'],
        ]);
    }

    /** @return array{chasse_id:int,enigme_id:int,label:string}|null */
    private static function buildToggle(
        bool $hasRiddleHints,
        int $huntId,
        int $riddleId,
        string $targetType
    ): ?array {
        if (!$hasRiddleHints || $huntId <= 0 || $riddleId <= 0) {
            return null;
        }

        return [
            'chasse_id' => $huntId,
            'enigme_id' => $riddleId,
            'label' => $targetType === 'enigme'
                ? __('Voir tous les indices de la chasse', 'chassesautresor-com')
                : __('Voir les indices de cette énigme', 'chassesautresor-com'),
        ];
    }
}
