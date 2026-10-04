<?php

use PHPUnit\Framework\TestCase;

class MyAccountSidebarNavTest extends TestCase
{
    private function stubAccountHelpers(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }

        if (!function_exists('__')) {
            eval('function __($text, $domain = null){return $text;}');
        }
        if (!function_exists('esc_html__')) {
            eval('function esc_html__($text, $domain = null){return $text;}');
        }
        if (!function_exists('wc_get_account_endpoint_url')) {
            eval('function wc_get_account_endpoint_url($endpoint){return "https://example.com/mon-compte/$endpoint/";}');
        }
        if (!function_exists('is_wc_endpoint_url')) {
            eval('function is_wc_endpoint_url($endpoint = ""){return false;}');
        }
        if (!function_exists('is_account_page')) {
            eval('function is_account_page(){return true;}');
        }
        if (!function_exists('wp_get_current_user')) {
            eval('function wp_get_current_user(){return (object)["ID"=>1,"roles"=>["subscriber"]];}');
        }
        if (!function_exists('add_action')) {
            eval('function add_action($hook, $callback, $priority = 10, $accepted_args = 1){}');
        }
        if (!function_exists('add_filter')) {
            eval('function add_filter($hook, $callback, $priority = 10, $accepted_args = 1){}');
        }
        if (!function_exists('remove_action')) {
            eval('function remove_action($hook, $callback, $priority = 10){return true;}');
        }
        if (!class_exists('WP_User')) {
            eval('class WP_User { public $ID = 0; public $roles = []; }');
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_player_nav_includes_tentatives_and_settings(): void
    {
        $this->stubAccountHelpers();

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/myaccount-functions.php';

        $user = new WP_User();
        $user->ID = 1;
        $user->roles = ['subscriber'];

        $items = myaccount_get_sidebar_nav_items($user);
        $labels = array_column($items, 'label');

        $this->assertSame(['Accueil', 'Tentatives', 'Réglages'], $labels);
        $this->assertFalse(in_array('Commandes', $labels, true));
        $this->assertFalse(in_array('Profil', $labels, true));
        $this->assertFalse(in_array('Déconnexion', $labels, true));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_organizer_nav_hides_tentatives(): void
    {
        $this->stubAccountHelpers();

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/myaccount-functions.php';

        $user = new WP_User();
        $user->ID = 2;
        $user->roles = ['organisateur'];

        $player = new WP_User();
        $player->ID = 3;
        $player->roles = ['subscriber'];

        $items = myaccount_get_sidebar_nav_items($user);
        $labels = array_column($items, 'label');

        $this->assertSame(['Accueil', 'Réglages'], $labels);
        $this->assertTrue(myaccount_user_is_player($player));
        $this->assertFalse(myaccount_user_is_player($user));
    }
}
