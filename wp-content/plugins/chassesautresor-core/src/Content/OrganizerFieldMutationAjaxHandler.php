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

        $mutation = (new OrganizerMutationService())->apply(
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
        if ($mutation['error'] !== null) {
            wp_send_json_error(self::getErrorMessage($mutation['error']));
        }

        wp_send_json_success([
            'champ' => $mutation['field'],
            'valeur' => $mutation['value'],
        ]);
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
