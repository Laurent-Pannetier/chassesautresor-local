<?php
use PHPUnit\Framework\TestCase;

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        return true;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($cap) {
        return false;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 10;
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($id) {
        return $id === 5 ? 'organisateur' : 'chasse';
    }
}

if (!function_exists('get_organisateur_chasse')) {
    function get_organisateur_chasse($chasse_id) {
        return 5;
    }
}

if (!function_exists('get_organisateur_from_chasse')) {
    function get_organisateur_from_chasse($chasse_id) {
        return 0;
    }
}

if (!function_exists('get_field')) {
    function get_field($field, $post_id) {
        global $fields, $post_fields;
        if ($field === 'utilisateurs_associes' && $post_id === 5) {
            return ['10'];
        }
        return $fields[$post_id][$field] ?? $post_fields[$post_id][$field] ?? null;
    }
}

if (!function_exists('get_post_field')) {
    function get_post_field($field, $post_id) {
        return 20;
    }
}

if (!function_exists('cat_debug')) {
    function cat_debug(...$args): void {}
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/access-functions.php';

class UtilisateurPeutModifierPostTest extends TestCase
{
    public function test_chasse_permission_uses_get_organisateur_chasse(): void
    {
        $this->assertTrue(utilisateur_peut_modifier_post(100));
    }
}
