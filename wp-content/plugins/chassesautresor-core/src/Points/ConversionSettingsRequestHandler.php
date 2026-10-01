<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

final class ConversionSettingsRequestHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('init', [self::class, 'initialize']);
        $addAction('init', [self::class, 'handleUpdate']);
    }

    public static function initialize(): void
    {
        (new ConversionSettingsService())->initialize();
    }

    public static function handleUpdate(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !isset($_POST['enregistrer_taux'])) {
            return;
        }

        $nonce = isset($_POST['modifier_taux_conversion_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['modifier_taux_conversion_nonce']))
            : '';
        if (!wp_verify_nonce($nonce, 'modifier_taux_conversion_action')) {
            wp_die(__('❌ Vérification du nonce échouée.', 'chassesautresor-com'));
        }
        if (!current_user_can('administrator')) {
            wp_die(__('❌ Accès refusé.', 'chassesautresor-com'));
        }

        $rate = isset($_POST['nouveau_taux']) ? (float) wp_unslash($_POST['nouveau_taux']) : 0.0;
        if (!(new ConversionSettingsService())->updateRate($rate)) {
            wp_die(__('❌ Veuillez entrer un taux de conversion valide.', 'chassesautresor-com'));
        }

        $referer = wp_get_referer() ?: home_url('/mon-compte/');
        wp_safe_redirect(add_query_arg('taux_mis_a_jour', '1', $referer));
        exit;
    }
}
