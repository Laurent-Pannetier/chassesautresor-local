<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * WordPress AJAX adapter for individual hunt field mutations.
 */
class HuntFieldMutationAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_modifier_champ_chasse', [self::class, 'handle'], 10, 1);
        $addAction('wp_ajax_enregistrer_champs_chasse', [self::class, 'handleBatch'], 10, 1);
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
        if (!utilisateur_peut_modifier_post($huntId)) {
            wp_send_json_error('⚠️ acces_refuse');
        }

        $accessError = self::fieldAccessError($huntId, $field, $value);
        if ($accessError !== null) {
            wp_send_json_error($accessError);
        }

        $result = self::applyField($huntId, $field, $value);
        if ($result['error'] !== null) {
            wp_send_json_error($result['error']);
        }
        if ($result['payload'] !== null) {
            wp_send_json_success($result['payload']);
        }
        if ($result['recalculate_status'] || self::triggersStatusUpdate($field)) {
            wp_cache_delete($huntId, 'post');
            sleep(1);
            do_action('chassesautresor_hunt_fields_updated', $huntId);
        }

        wp_send_json_success(['champ' => $field, 'valeur' => $value]);
    }

    public static function handleBatch(): void {
        check_ajax_referer('hunt_field_management', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }

        $huntId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($huntId <= 0 || get_post_type($huntId) !== 'chasse') {
            wp_send_json_error('⚠️ post_invalide');
        }
        if (!utilisateur_peut_modifier_post($huntId)
            || !(new WordPressContentAccessResolver())->canEditFields($huntId)
        ) {
            wp_send_json_error('⚠️ acces_refuse');
        }

        $fields = self::postedFields();
        $dates = self::postedDates();
        if ($fields === [] && $dates === null) {
            wp_send_json_error('⚠️ donnees_invalides');
        }

        $recalculate = false;
        $batch = (new ShellEditionFieldBatch())->apply(
            $fields,
            static function (string $field, $value) use ($huntId, &$recalculate): ?string {
                if (self::isClosureField($field, $value)) {
                    return __('⚠️ champ_non_autorise', 'chassesautresor-com');
                }

                $result = self::applyField($huntId, $field, $value);
                if ($result['error'] !== null) {
                    return $result['error'];
                }

                $recalculate = $recalculate
                    || $result['recalculate_status']
                    || self::triggersStatusUpdate($field);

                return null;
            }
        );

        if ($batch['error'] === null && $dates !== null) {
            $dateError = self::applyDates($huntId, $dates);
            if ($dateError !== null) {
                $batch['error'] = $dateError;
                $batch['failed'] = 'dates';
            } else {
                $batch['saved'][] = 'dates';
                $recalculate = true;
            }
        }

        if ($batch['saved'] !== [] && $recalculate) {
            wp_cache_delete($huntId, 'post');
            do_action('chassesautresor_hunt_status_refresh_requested', $huntId);
        }

        if ($batch['error'] !== null) {
            wp_send_json_error([
                'message' => $batch['error'],
                'champ' => $batch['failed'],
                'enregistres' => $batch['saved'],
            ]);
        }

        wp_send_json_success(['enregistres' => $batch['saved']]);
    }

    /**
     * @param mixed $value
     * @return array{error:?string,recalculate_status:bool,payload:?array<string,mixed>}
     */
    private static function applyField(int $huntId, string $field, $value): array {
        if ($field === 'post_title') {
            $updated = wp_update_post(['ID' => $huntId, 'post_title' => $value], true);
            if (is_wp_error($updated)) {
                return self::fieldResult('⚠️ echec_update_post_title', false, null);
            }

            return self::fieldResult(null, false, null);
        }

        if ($field === 'chasse_principale_liens') {
            return self::applyLinks($huntId, $field, (string) $value);
        }

        $handled = false;
        $recalculateStatus = false;
        $reward = (new HuntRewardMutationService())->apply($huntId, $field, $value, 'update_field');
        $rewardError = self::mutationErrorMessage($reward['error']);
        if ($rewardError !== null) {
            return self::fieldResult($rewardError, false, null);
        }
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
        $mutationError = self::mutationErrorMessage($mutation['error']);
        if ($mutationError !== null) {
            return self::fieldResult($mutationError, false, null);
        }
        if ($mutation['handled']) {
            $handled = true;
            $recalculateStatus = $recalculateStatus || $mutation['recalculate_status'];
        }

        $closure = (new HuntClosureService())->apply(
            $huntId,
            $field,
            $value,
            'update_field',
            static function (int $id): array {
                return array_map(
                    'intval',
                    get_posts((new \ChassesAuTresor\Core\Relationships\HuntRiddleQueryService())
                        ->getRiddleIdsQueryArgs($id))
                );
            },
            [RiddleSolutionFileScheduler::class, 'schedule'],
            static function (int $id, string $type) {
                $query = (new SolutionQueryService())->getActiveSolutionQueryArgs($id, $type);
                $solutions = $query === [] ? [] : get_posts($query);

                return $solutions[0] ?? null;
            },
            [SolutionPublicationPlanner::class, 'plan'],
            [\ChassesAuTresor\Core\Progress\HuntCompletionHookHandler::class, 'completeHunt']
        );
        $closureError = self::mutationErrorMessage(
            is_array($closure) ? ($closure['error'] ?? null) : 'echec_mise_a_jour'
        );
        if ($closureError !== null) {
            return self::fieldResult($closureError, false, null);
        }
        $handled = $handled || (is_array($closure) && !empty($closure['handled']));

        if (!$handled) {
            return self::fieldResult(
                __('⚠️ champ_non_autorise', 'chassesautresor-com'),
                false,
                null
            );
        }

        return self::fieldResult(null, $recalculateStatus, null);
    }

    /**
     * @return array{error:?string,recalculate_status:bool,payload:?array<string,mixed>}
     */
    private static function applyLinks(int $huntId, string $field, string $value): array {
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

            return self::fieldResult($message, false, null);
        }

        return self::fieldResult(null, false, [
            'champ' => $field,
            'valeur' => $mutation['value'],
        ]);
    }

    /**
     * @param array{date_debut:string,date_fin:string,illimitee:bool,debut_differee:bool} $dates
     */
    private static function applyDates(int $huntId, array $dates): ?string {
        $mutation = (new HuntDateMutationService())->apply(
            $huntId,
            $dates['date_debut'],
            $dates['date_fin'],
            $dates['illimitee'],
            $dates['debut_differee'],
            [HuntDateMutationAjaxHandler::class, 'parseDate'],
            'update_field',
            'update_post_meta',
            'get_post_meta'
        );
        if ($mutation['error'] === null) {
            return null;
        }

        $messages = [
            'format_debut_invalide' => __('⚠️ format_date_invalide', 'chassesautresor-com'),
            'format_fin_invalide' => __('⚠️ format_date_invalide', 'chassesautresor-com'),
            'date_fin_avant_debut' => __('⚠️ date_fin_avant_debut', 'chassesautresor-com'),
            'echec_mise_a_jour' => __('⚠️ echec_mise_a_jour', 'chassesautresor-com'),
        ];

        return $messages[$mutation['error']] ?? $mutation['error'];
    }

    /**
     * @return array<string, mixed>
     */
    private static function postedFields(): array {
        $raw = $_POST['champs'] ?? null;
        if (!is_array($raw)) {
            return [];
        }

        $fields = [];
        foreach ($raw as $key => $value) {
            $field = sanitize_text_field((string) $key);
            if ($field === '') {
                continue;
            }
            $fields[$field] = is_array($value) ? $value : wp_kses_post((string) $value);
        }

        return $fields;
    }

    /**
     * @return array{date_debut:string,date_fin:string,illimitee:bool,debut_differee:bool}|null
     */
    private static function postedDates(): ?array {
        if (!isset($_POST['dates']) || !is_array($_POST['dates'])) {
            return null;
        }

        $dates = $_POST['dates'];

        return [
            'date_debut' => sanitize_text_field((string) ($dates['date_debut'] ?? '')),
            'date_fin' => sanitize_text_field((string) ($dates['date_fin'] ?? '')),
            'illimitee' => !empty($dates['illimitee']),
            'debut_differee' => !empty($dates['debut_differee']),
        ];
    }

    private static function fieldAccessError(int $huntId, string $field, $value): ?string {
        $requiresFullAccess = !self::isClosureField($field, $value)
            && $field !== 'chasse_principale_liens';
        if ($requiresFullAccess
            && !(new WordPressContentAccessResolver())->canEditFields($huntId)
        ) {
            return '⚠️ acces_refuse';
        }

        return null;
    }

    private static function mutationErrorMessage(?string $error): ?string {
        if ($error === null) {
            return null;
        }

        $messages = [
            'format_date_invalide' => __('⚠️ format_date_invalide', 'chassesautresor-com'),
            'valeur_invalide' => __('⚠️ valeur_invalide', 'chassesautresor-com'),
            'echec_mise_a_jour' => __('⚠️ echec_mise_a_jour', 'chassesautresor-com'),
        ];

        return $messages[$error] ?? $error;
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{error:?string,recalculate_status:bool,payload:?array<string,mixed>}
     */
    private static function fieldResult(?string $error, bool $recalculateStatus, ?array $payload): array {
        return [
            'error' => $error,
            'recalculate_status' => $recalculateStatus,
            'payload' => $payload,
        ];
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
