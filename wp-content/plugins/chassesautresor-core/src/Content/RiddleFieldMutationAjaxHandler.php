<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Media\RiddleImageProtectionLifecycle;
use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * WordPress AJAX adapter for individual riddle field mutations.
 */
class RiddleFieldMutationAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_modifier_champ_enigme', [self::class, 'handle'], 10, 1);
        $addAction('wp_ajax_enregistrer_champs_enigme', [self::class, 'handleBatch'], 10, 1);
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
        self::assertCanEdit($riddleId);

        $wasComplete = (bool) get_field('enigme_cache_complet', $riddleId);
        $mutation = self::mutate($riddleId, $field, $value);
        if ($mutation['error'] !== null) {
            wp_send_json_error($mutation['error']);
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

    public static function handleBatch(): void {
        check_ajax_referer('modifier_champ_enigme', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }

        $riddleId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($riddleId <= 0 || get_post_type($riddleId) !== 'enigme') {
            wp_send_json_error('⚠️ donnees_invalides');
        }
        self::assertCanEdit($riddleId);

        $fields = self::postedFields();
        if ($fields === []) {
            wp_send_json_error('⚠️ donnees_invalides');
        }

        $refreshState = false;
        $batch = (new ShellEditionFieldBatch())->apply(
            $fields,
            static function (string $field, $value) use ($riddleId, $fields, &$refreshState): ?string {
                if ($field === 'enigme_acces_condition' && (string) $value === 'pre_requis') {
                    $error = self::applyPrerequisiteCondition($riddleId, $fields);
                    if ($error === null) {
                        $refreshState = true;
                    }

                    return $error;
                }

                $mutation = self::mutate($riddleId, $field, $value);
                if ($mutation['error'] !== null) {
                    return $mutation['error'];
                }

                $refreshState = $refreshState || $mutation['refresh_state'];

                return null;
            }
        );

        if ($batch['saved'] !== []) {
            if ($refreshState) {
                do_action('chassesautresor_riddle_state_refresh_requested', $riddleId);
            }
            do_action('chassesautresor_riddle_completeness_refresh_requested', $riddleId);
        }

        $payload = ['enregistres' => $batch['saved']];
        if (in_array('enigme_visuel_image', $batch['saved'], true)) {
            RiddleImageProtectionLifecycle::protectAfterSave($riddleId);
            RiddleImageProtectionLifecycle::restoreAfterSave($riddleId);
            $payload['vignettes'] = self::imageThumbs($riddleId);
        }

        if ($batch['error'] !== null) {
            $payload['message'] = $batch['error'];
            $payload['champ'] = $batch['failed'];
            wp_send_json_error($payload);
        }

        wp_send_json_success($payload);
    }

    /**
     * @return list<string>
     */
    private static function imageThumbs(int $riddleId): array {
        $ids = function_exists('get_field') ? get_field('enigme_visuel_image', $riddleId, false) : [];
        if (!is_array($ids) || !function_exists('cta_voir_image_enigme_url')) {
            return [];
        }

        $thumbs = [];
        foreach ($ids as $id) {
            $imageId = (int) $id;
            if ($imageId <= 0) {
                continue;
            }
            $thumbs[] = cta_voir_image_enigme_url($imageId, 'thumbnail');
        }

        return $thumbs;
    }

    /**
     * @return array{error:?string,terminal:bool,refresh_state:bool}
     */
    private static function mutate(int $riddleId, string $field, $value): array {
        $policy = new RiddleFieldPolicyService();
        if (!$policy->isEditableField($field)) {
            return self::mutationResult('⚠️ champ_interdit', false, false);
        }
        if ($field === 'enigme_acces_condition'
            && $policy->isForbiddenAccessCondition((string) $value)
        ) {
            return self::mutationResult(
                __('⚠️ Interdit : cette valeur est gérée automatiquement.', 'chassesautresor-com'),
                false,
                false
            );
        }

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
            return self::mutationResult('⚠️ ' . $mutation['error'], false, false);
        }

        return self::mutationResult(null, $mutation['terminal'], $mutation['refresh_state']);
    }

    /**
     * @param array<string, mixed> $fields
     */
    private static function applyPrerequisiteCondition(int $riddleId, array $fields): ?string {
        $source = array_key_exists('enigme_acces_pre_requis', $fields)
            ? $fields['enigme_acces_pre_requis']
            : get_field('enigme_acces_pre_requis', $riddleId);
        $ids = (new RiddleFieldPolicyService())->normalizePrerequisiteIds($source ?? []);
        if ($ids === []) {
            return __('⚠️ Aucun prérequis sélectionné.', 'chassesautresor-com');
        }

        $updated = update_field('enigme_acces_condition', 'pre_requis', $riddleId);
        if ($updated) {
            return null;
        }

        $stored = (string) get_field('enigme_acces_condition', $riddleId);

        return $stored === 'pre_requis' ? null : '⚠️ echec_mise_a_jour_final';
    }

    private static function assertCanEdit(int $riddleId): void {
        if (!utilisateur_peut_modifier_post($riddleId)
            || !(new WordPressContentAccessResolver())->canEditFields($riddleId)
        ) {
            wp_send_json_error('⚠️ acces_refuse');
        }
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
            $fields[$field] = $value;
        }

        return $fields;
    }

    /**
     * @return array{error:?string,terminal:bool,refresh_state:bool}
     */
    private static function mutationResult(?string $error, bool $terminal, bool $refreshState): array {
        return [
            'error' => $error,
            'terminal' => $terminal,
            'refresh_state' => $refreshState,
        ];
    }
}
