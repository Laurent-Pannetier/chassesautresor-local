<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Presentation;

/** Load neutral, functional assets without duplicating the historical theme bundle. */
final class FunctionalAssetManager {
    public static function register(callable $addAction): void {
        $addAction('wp_enqueue_scripts', [self::class, 'enqueue']);
    }

    public static function enqueue(): void {
        if (!is_singular(['chasse', 'enigme', 'organisateur'])) {
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
        ]);
    }
}
