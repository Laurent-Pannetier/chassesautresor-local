<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * WordPress AJAX adapter for hint creation and edition modals.
 */
class HintModalAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_creer_indice_modal', [self::class, 'create'], 10, 1);
        $addAction('wp_ajax_modifier_indice_modal', [self::class, 'update'], 10, 1);
        $addAction('wp_ajax_indice_lister_prerequis', [self::class, 'listPrerequisites'], 10, 1);
    }

    public static function create(): void {
        self::guardRequest();
        [$targetId, $targetType] = self::getTarget();
        $linkedRiddleId = isset($_POST['indice_enigme_linked'])
            ? (int) $_POST['indice_enigme_linked']
            : 0;
        if (!(new HintCreationService())->hasConsistentRiddleTarget(
            $targetType,
            $targetId,
            $linkedRiddleId
        )) {
            wp_send_json_error('post_invalide');
        }
        if (!(new RelatedContentAccessResolver())->canPerform('create', $targetType, $targetId)) {
            wp_send_json_error('acces_refuse');
        }

        $access = self::accessConfiguration(0, $targetType, $targetId);
        $hintId = HintCreationRouteHandler::create($targetId, $targetType);
        if (is_wp_error($hintId)) {
            wp_send_json_error($hintId->get_error_message());
        }
        $hintId = (int) $hintId;
        if ($hintId <= 0) {
            wp_send_json_error('echec_creation');
        }

        self::applyMutation($hintId, false, $access);
        wp_send_json_success(['indice_id' => $hintId]);
    }

    public static function update(): void {
        self::guardRequest();
        [$targetId, $targetType] = self::getTarget();
        $hintId = isset($_POST['indice_id']) ? (int) $_POST['indice_id'] : 0;
        if ($hintId <= 0 || get_post_type($hintId) !== 'indice') {
            wp_send_json_error('indice_invalide');
        }
        if (!(new RelatedContentAccessResolver())->canPerform('edit', $targetType, $targetId)) {
            wp_send_json_error('acces_refuse');
        }

        self::applyMutation($hintId, true, self::accessConfiguration($hintId, $targetType, $targetId));
        wp_send_json_success(['indice_id' => $hintId]);
    }

    public static function listPrerequisites(): void {
        self::guardRequest();
        [$targetId, $targetType] = self::getTarget();
        $hintId = isset($_POST['indice_id']) ? (int) $_POST['indice_id'] : 0;
        $access = new RelatedContentAccessResolver();
        if (!$access->canPerform('edit', $targetType, $targetId)
            && !$access->canPerform('create', $targetType, $targetId)
        ) {
            wp_send_json_error('acces_refuse');
        }

        $hints = [];
        foreach (self::eligibleIds($targetId, $targetType, $hintId) as $id) {
            $hints[] = [
                'id' => $id,
                'title' => self::hintLabel($id),
            ];
        }
        wp_send_json_success(['indices' => $hints]);
    }

    private static function guardRequest(): void {
        check_ajax_referer('hint_management', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }
    }

    /** @return array{0:int,1:string} */
    private static function getTarget(): array {
        $targetId = isset($_POST['objet_id']) ? (int) $_POST['objet_id'] : 0;
        $targetType = sanitize_key($_POST['objet_type'] ?? '');
        if ($targetId <= 0
            || !in_array($targetType, ['chasse', 'enigme'], true)
            || get_post_type($targetId) !== $targetType
        ) {
            wp_send_json_error('post_invalide');
        }

        return [$targetId, $targetType];
    }

    /**
     * @return array{error:?string,prerequisites:int[],delay_minutes:int,anchor:string}
     */
    private static function accessConfiguration(int $hintId, string $targetType, int $targetId): array {
        $requested = isset($_POST['indice_acces_pre_requis'])
            ? (array) $_POST['indice_acces_pre_requis']
            : [];
        $policy = new HintAccessPolicy();
        $eligible = self::eligibleIds($targetId, $targetType, $hintId);
        $normalized = $policy->normalize(
            $hintId,
            $requested,
            $eligible,
            self::prerequisiteGraph($eligible),
            isset($_POST['indice_delai_minutes']) ? (int) $_POST['indice_delai_minutes'] : 0,
            sanitize_key((string) ($_POST['indice_delai_ancrage'] ?? HintAccessPolicy::ANCHOR_NONE))
        );
        if ($normalized['error'] !== null) {
            wp_send_json_error($normalized['error']);
        }

        return $normalized;
    }

    /** @return int[] */
    private static function eligibleIds(int $targetId, string $targetType, int $hintId): array {
        $args = (new HintQueryService())->getRankedHintIdsQueryArgs($targetId, $targetType);
        if ($args === [] || !function_exists('get_posts')) {
            return [];
        }

        $eligible = [];
        foreach ((array) get_posts($args) as $id) {
            $id = (int) $id;
            if ($id <= 0 || $id === $hintId) {
                continue;
            }
            if ($targetType === 'chasse' && (string) get_field('indice_cible_type', $id) === 'enigme') {
                continue;
            }
            $eligible[] = $id;
        }

        return $eligible;
    }

    /**
     * @param int[] $eligibleIds
     * @return array<int, int[]>
     */
    private static function prerequisiteGraph(array $eligibleIds): array {
        $policy = new HintAccessPolicy();
        $graph = [];
        foreach ($eligibleIds as $id) {
            $graph[$id] = $policy->normalizeIds((array) get_field('indice_acces_pre_requis', $id));
        }

        return $graph;
    }

    private static function hintLabel(int $hintId): string {
        $post = get_post($hintId);
        $title = $post ? (string) $post->post_title : '';
        $rank = (int) get_post_meta($hintId, 'indice_rank', true);
        $default = defined('TITRE_DEFAUT_INDICE') ? (string) TITRE_DEFAUT_INDICE : '';
        $prefix = defined('INDICE_DEFAULT_PREFIX') ? (string) INDICE_DEFAULT_PREFIX : '';
        if ((new HintTitleService())->shouldRegenerate($title, $default, $prefix)) {
            return sprintf(__('Indice #%d', 'chassesautresor-com'), max(1, $rank));
        }

        return $title;
    }

    /**
     * @param array{prerequisites:int[],delay_minutes:int,anchor:string} $access
     */
    private static function applyMutation(int $hintId, bool $replaceOptionalFields, array $access): void {
        (new HintMutationService())->applyModal(
            $hintId,
            isset($_POST['indice_image']) ? (int) $_POST['indice_image'] : 0,
            wp_kses_post($_POST['indice_contenu'] ?? ''),
            sanitize_key($_POST['indice_disponibilite'] ?? 'immediate'),
            sanitize_text_field($_POST['indice_date_disponibilite'] ?? ''),
            (string) get_field('indice_date_disponibilite', $hintId),
            wp_date('Y-m-d H:i:s', (int) current_time('timestamp')),
            $replaceOptionalFields,
            static fn (string $field, $value, int $postId) => update_field($field, $value, $postId),
            static fn (string $field, int $postId) => delete_field($field, $postId),
            static fn (int $postId) => do_action('chassesautresor_hint_cache_refresh_requested', $postId),
            $access
        );
    }
}
