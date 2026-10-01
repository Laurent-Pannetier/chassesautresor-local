<?php

declare(strict_types=1);

if (!function_exists('is_woocommerce_account_page')) {
    /** Determine whether the current request targets a supported WooCommerce account endpoint. */
    function is_woocommerce_account_page(): bool {
        $path = wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $path = is_string($path) ? trailingslashit($path) : '';
        $pages = [
            '/mon-compte/commandes/',
            '/mon-compte/voir-commandes/',
            '/mon-compte/modifier-adresse/',
            '/mon-compte/modifier-compte/',
            '/mon-compte/telechargements/',
            '/mon-compte/moyens-paiement/',
            '/mon-compte/lost-password/',
            '/mon-compte/customer-logout/',
        ];

        foreach ($pages as $page) {
            if (strpos($path, $page) === 0) {
                return true;
            }
        }

        return false;
    }
}
