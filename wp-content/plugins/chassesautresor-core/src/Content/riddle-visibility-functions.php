<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddlePaymentPolicy;

if (!function_exists('enigme_is_paid')) {
    /**
     * An enigma is paid when its attempt cost is positive and validation is active.
     */
    function enigme_is_paid(int $enigme_id): bool
    {
        if ($enigme_id <= 0 || !function_exists('get_post_type') || get_post_type($enigme_id) !== 'enigme') {
            return false;
        }

        $cost = function_exists('get_field') ? (int) get_field('enigme_tentative_cout_points', $enigme_id) : 0;
        $mode = function_exists('get_field') ? (string) get_field('enigme_mode_validation', $enigme_id) : '';

        return RiddlePaymentPolicy::isPaid($cost, $mode);
    }
}

if (!function_exists('filter_visible_enigmes')) {
    /**
     * Keep published, complete enigmas the player is allowed to see.
     *
     * @param array<int, mixed> $enigmes
     * @return array<int, mixed>
     */
    function filter_visible_enigmes(array $enigmes, int $user_id): array
    {
        return array_values(array_filter($enigmes, static function ($post) use ($user_id) {
            $id = is_object($post) ? (int) $post->ID : (int) $post;
            if ($id <= 0 || !function_exists('get_post_status') || get_post_status($id) !== 'publish') {
                return false;
            }
            if (!function_exists('get_field') || !get_field('enigme_cache_complet', $id)) {
                return false;
            }

            return function_exists('enigme_est_visible_pour') && enigme_est_visible_pour($user_id, $id);
        }));
    }
}
