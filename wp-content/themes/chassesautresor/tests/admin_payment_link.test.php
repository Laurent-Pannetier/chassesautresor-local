<?php
use PHPUnit\Framework\TestCase;

if (!function_exists('current_user_can')) {
    function current_user_can($cap) {
        return true;
    }
}
if (!function_exists('get_users')) {
    function get_users($args) {
        return [ (object) ['ID' => 1, 'display_name' => 'Admin'] ];
    }
}
if (!function_exists('get_user_meta')) {
    function get_user_meta($user_id, $key, $single) {
        return [
            [
                'statut'                    => 'en attente',
                'paiement_demande_montant'  => 10,
                'paiement_date_demande'     => '2024-01-01 10:00:00',
                'paiement_points_utilises'  => 20,
            ],
        ];
    }
}
if (!function_exists('maybe_unserialize')) {
    function maybe_unserialize($value) {
        return $value;
    }
}
if (!function_exists('get_organisateur_from_user')) {
    function get_organisateur_from_user($user_id) {
        return null;
    }
}
if (!function_exists('get_field')) {
    function get_field($key, $id) {
        return '';
    }
}
if (!function_exists('esc_html')) {
    function esc_html($text) {
        return $text;
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return $text;
    }
}
if (!function_exists('home_url')) {
    function home_url($path = '') {
        return 'https://example.com' . $path;
    }
}
if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url = '') {
        $query = http_build_query($args);
        if ($url === '') {
            $url = 'https://example.com/admin-ajax.php';
        }
        $sep = strpos($url, '?') === false ? '?' : '&';
        return $url . $sep . $query;
    }
}

require_once __DIR__ . '/../inc/admin-functions.php';

class AdminPaymentLinkTest extends TestCase
{
    public function test_payment_link_uses_frontend_url()
    {
        ob_start();
        afficher_tableau_paiements_admin();
        $html = ob_get_clean();
        $this->assertStringContainsString(
            'https://example.com/mon-compte/organisateurs/?regler_paiement=0&user_id=1',
            $html
        );
    }
}
