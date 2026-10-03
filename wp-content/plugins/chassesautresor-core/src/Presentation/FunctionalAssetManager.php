<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Presentation;

/** Load neutral, functional assets without duplicating the historical theme bundle. */
final class FunctionalAssetManager {
    public static function register(callable $addAction): void {
        $addAction('wp_enqueue_scripts', [self::class, 'enqueue']);
    }

    public static function enqueue(): void {
        $postTypes = ['chasse', 'enigme', 'organisateur'];
        $accountPage = function_exists('is_account_page') && is_account_page();
        if (!is_singular($postTypes) && !is_post_type_archive($postTypes) && !$accountPage) {
            return;
        }
        if (get_stylesheet() === 'chassesautresor') {
            return;
        }

        $baseUrl = plugin_dir_url(dirname(__DIR__, 2) . '/chassesautresor-core.php');
        $basePath = dirname(__DIR__, 2);
        wp_enqueue_style(
            'chassesautresor-core-public',
            $baseUrl . 'assets/css/public.css',
            [],
            (string) filemtime($basePath . '/assets/css/public.css')
        );
        wp_enqueue_script(
            'chassesautresor-core-public',
            $baseUrl . 'assets/js/public.js',
            [],
            (string) filemtime($basePath . '/assets/js/public.js'),
            true
        );
        wp_localize_script('chassesautresor-core-public', 'chassesAuTresorCore', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'errorMessage' => __('Une erreur est survenue. Veuillez réessayer.', 'chassesautresor-com'),
            'successMessage' => __('Votre réponse a bien été enregistrée.', 'chassesautresor-com'),
            'hintNonce' => wp_create_nonce('unlock_hint'),
            'unlockHint' => __('Débloquer cet indice', 'chassesautresor-com'),
            'close' => __('Fermer', 'chassesautresor-com'),
        ]);
        if ($accountPage) {
            wp_enqueue_script(
                'chassesautresor-core-account',
                $baseUrl . 'assets/js/account.js',
                [],
                (string) filemtime($basePath . '/assets/js/account.js'),
                true
            );
            wp_localize_script('chassesautresor-core-account', 'chassesAuTresorAccount', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cat_account_section'),
                'loading' => __('Chargement…', 'chassesautresor-com'),
                'error' => __('Impossible de charger cette section.', 'chassesautresor-com'),
            ]);
        }
    }
}
