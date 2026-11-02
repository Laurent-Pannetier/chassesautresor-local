<?php

declare(strict_types=1);

if (!function_exists('cat_product_edition_url')) {
    /**
     * Edition URL. The active theme may replace the fallback through
     * the chassesautresor_edition_url filter.
     */
    function cat_product_edition_url(string $type, int $id, string $section): string
    {
        $home = function_exists('home_url') ? home_url('/outils/') : '/outils/';
        $fallback = function_exists('add_query_arg')
            ? add_query_arg([$type => $id, 'section' => $section], $home)
            : $home;
        if (!function_exists('apply_filters')) {
            return $fallback;
        }

        return (string) apply_filters('chassesautresor_edition_url', $fallback, $type, $id, $section);
    }
}
