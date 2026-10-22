<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * WordPress AJAX adapter for organizer field mutations.
 */
class OrganizerFieldMutationAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_modifier_champ_organisateur', [self::class, 'handle'], 10, 1);
        $addAction('wp_ajax_enregistrer_champs_organisateur', [self::class, 'handleBatch'], 10, 1);
    }

    public static function handle(): void {
        check_ajax_referer('organizer_management', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }

        $field = sanitize_text_field($_POST['champ'] ?? '');
        $value = wp_kses_post($_POST['valeur'] ?? '');
        $organizerId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($organizerId > 0 && get_post_type($organizerId) === 'chasse') {
            $organizerId = (new RelationshipService())->normalizeId(
                get_field('chasse_cache_organisateur', $organizerId)
            ) ?? 0;
        }

        if ($field === '' || !isset($_POST['valeur'])) {
            wp_send_json_error('⚠️ donnees_invalides');
        }
        if ($organizerId <= 0 || get_post_type($organizerId) !== 'organisateur') {
            wp_send_json_error('⚠️ organisateur_introuvable');
        }
        if (!utilisateur_peut_modifier_post($organizerId)
            || !(new WordPressContentAccessResolver())->canEditFields($organizerId)
        ) {
            wp_send_json_error('⚠️ acces_refuse');
        }

        $mutation = self::applyMutation($organizerId, $field, $value);
        if ($mutation['error'] !== null) {
            wp_send_json_error(self::getErrorMessage($mutation['error']));
        }

        wp_send_json_success([
            'champ' => $mutation['field'],
            'valeur' => $mutation['value'],
        ]);
    }

    public static function handleBatch(): void {
        check_ajax_referer('organizer_management', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }

        $organizerId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($organizerId <= 0 || get_post_type($organizerId) !== 'organisateur') {
            wp_send_json_error('⚠️ organisateur_introuvable');
        }
        if (!utilisateur_peut_modifier_post($organizerId)
            || !(new WordPressContentAccessResolver())->canEditFields($organizerId)
        ) {
            wp_send_json_error('⚠️ acces_refuse');
        }

        $fields = self::postedFields();
        if ($fields === []) {
            wp_send_json_error('⚠️ donnees_invalides');
        }

        $batch = (new ShellEditionFieldBatch())->apply(
            $fields,
            static function (string $field, $value) use ($organizerId): ?string {
                $mutation = self::applyMutation($organizerId, $field, $value);
                if ($mutation['error'] === null) {
                    return null;
                }

                return self::getErrorMessage($mutation['error']);
            }
        );

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
     * @return array{error: string|null, field: string, value: mixed}
     */
    private static function applyMutation(int $organizerId, string $field, $value): array {
        return (new OrganizerMutationService())->apply(
            $organizerId,
            $field,
            $value,
            'sanitize_text_field',
            'esc_url_raw',
            'wp_strip_all_tags',
            static fn (array $postData) => wp_update_post($postData, true),
            'is_wp_error',
            'update_field',
            'get_field',
            static fn (int $postId, string $metaKey) => get_post_meta($postId, $metaKey, true)
        );
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

    private static function getErrorMessage(string $error): string {
        $messages = [
            'description_too_short' => __(
                'Votre texte doit comporter au moins 50 caractères.',
                'chassesautresor-com'
            ),
            'title_update_failed' => '⚠️ echec_update_post_title',
            'invalid_format' => '⚠️ format_invalide',
            'links_update_failed' => '⚠️ echec_mise_a_jour_liens',
            'bank_details_update_failed' => '⚠️ echec_mise_a_jour_coordonnees',
            'field_update_failed' => '⚠️ echec_mise_a_jour_final',
            'field_not_allowed' => '⚠️ champ_non_autorise',
        ];

        return $messages[$error] ?? '⚠️ echec_mise_a_jour_final';
    }
}
