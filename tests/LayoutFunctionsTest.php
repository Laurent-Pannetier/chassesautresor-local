<?php

use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        // no-op
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        // no-op
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        return true;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('trouver_chasse_a_valider')) {
    function trouver_chasse_a_valider($user_id) {
        return 123;
    }
}

if (!function_exists('is_singular')) {
    function is_singular($post_type = '') {
        global $current_post_type;
        return $current_post_type === $post_type;
    }
}

if (!function_exists('get_the_ID')) {
    function get_the_ID() {
        global $current_post_id;
        return $current_post_id;
    }
}

if (!function_exists('recuperer_id_chasse_associee')) {
    function recuperer_id_chasse_associee($post_id = null) {
        return 123;
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($post_id) {
        return 'Chasse Exemple';
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post_id) {
        return 'https://example.com/chasse';
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return $url;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return $text;
    }
}

if (!function_exists('get_organisateur_from_user')) {
    function get_organisateur_from_user($user_id) {
        return 42;
    }
}

if (!function_exists('get_chasses_de_organisateur')) {
    function get_chasses_de_organisateur($organisateur_id) {
        return [(object) ['ID' => 123]];
    }
}

if (!function_exists('peut_valider_chasse')) {
    function peut_valider_chasse($chasse_id, $user_id) {
        return true;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/layout-functions.php';

class LayoutFunctionsTest extends TestCase
{
    private function getBannerOutput(): string
    {
        ob_start();
        afficher_bandeau_validation_chasse_global();
        return ob_get_clean();
    }

    public function test_banner_not_displayed_on_mon_compte_page(): void
    {
        global $current_post_type;
        $current_post_type = 'page';

        $this->assertSame('', $this->getBannerOutput());
    }

    public function test_banner_not_displayed_on_generic_page(): void
    {
        global $current_post_type;
        $current_post_type = 'page';

        $this->assertSame('', $this->getBannerOutput());
    }

    public function test_banner_not_displayed_on_other_cpt(): void
    {
        global $current_post_type;
        $current_post_type = 'chasse';

        $this->assertSame('', $this->getBannerOutput());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_banner_not_displayed_on_enigme_page(): void
    {
        global $current_post_type, $current_post_id;
        $current_post_type = 'enigme';
        $current_post_id = 456;

        $this->assertSame('', $this->getBannerOutput());
    }
}
