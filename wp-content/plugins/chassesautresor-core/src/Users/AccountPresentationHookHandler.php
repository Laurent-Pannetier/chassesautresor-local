<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Users;

/** Keep account navigation labels available independently of the active theme. */
final class AccountPresentationHookHandler {
    public static function register(callable $addFilter): void {
        $addFilter('pre_get_document_title', [self::class, 'filterDocumentTitle']);
        $addFilter('woocommerce_endpoint_orders_title', [self::class, 'ordersTitle']);
        $addFilter('woocommerce_endpoint_edit-account_title', [self::class, 'profileTitle']);
    }

    public static function filterDocumentTitle(string $title): string {
        global $wp;
        $request = isset($wp->request) ? trim((string) $wp->request, '/') : '';
        $titles = [
            'mon-compte/statistiques' => __('Statistiques - Chasses au Trésor', 'chassesautresor-com'),
            'mon-compte/outils' => __('Outils - Chasses au Trésor', 'chassesautresor-com'),
            'mon-compte/organisateurs' => __('Organisateur - Chasses au Trésor', 'chassesautresor-com'),
        ];

        return $titles[$request] ?? $title;
    }

    public static function ordersTitle(string $title): string {
        return __('Vos commandes', 'chassesautresor-com');
    }

    public static function profileTitle(string $title): string {
        return __('Profil', 'chassesautresor-com');
    }
}
