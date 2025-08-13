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
}
