<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Users;

/** Register functional shortcodes independently of the active theme. */
final class CoreShortcodeRegistrar {
    public static function register(callable $addShortcode): void {
        $addShortcode('afficher_points_utilisateur', [self::class, 'points']);
        $addShortcode('formulaire_reponse_manuelle', [self::class, 'manualAnswer']);
    }

    /** @param array<string,mixed> $attributes */
    public static function manualAnswer(array $attributes = []): string {
        $attributes = shortcode_atts(['id' => 0], $attributes);
        $riddleId = (int) $attributes['id'];
        if (function_exists('afficher_formulaire_reponse_manuelle')) {
            return (string) afficher_formulaire_reponse_manuelle($riddleId);
        }
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Veuillez vous connecter pour répondre à cette énigme.', 'chassesautresor-com')
                . '</p>';
        }
        if (!utilisateur_peut_repondre_manuelle((int) get_current_user_id(), $riddleId)) {
            return '<p>' . esc_html__('Vous ne pouvez plus répondre à cette énigme.', 'chassesautresor-com') . '</p>';
        }

        return '<form method="post" class="formulaire-reponse-manuelle">'
            . '<textarea name="reponse_manuelle" required></textarea>'
            . '<input type="hidden" name="enigme_id" value="' . esc_attr((string) $riddleId) . '">'
            . '<input type="hidden" name="reponse_manuelle_nonce" value="'
            . esc_attr(wp_create_nonce('reponse_manuelle_nonce')) . '">'
            . '<button type="submit">' . esc_html__('Valider', 'chassesautresor-com') . '</button></form>';
    }

    public static function points(): string {
        if (function_exists('afficher_points_utilisateur_callback')) {
            return (string) afficher_points_utilisateur_callback();
        }
        if (!is_user_logged_in()) {
            return '';
        }

        return '<span class="chassesautresor-points">'
            . esc_html(sprintf(__('%d pts', 'chassesautresor-com'), get_user_points(get_current_user_id())))
            . '</span>';
    }
}
