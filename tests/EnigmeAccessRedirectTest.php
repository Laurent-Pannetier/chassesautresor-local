<?php
use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Stubs for WordPress functions used before guest redirect check
if (!function_exists('is_singular')) {
    function is_singular($type)
    {
        return $GLOBALS['is_singular'] ?? false;
    }
}
if (!function_exists('get_queried_object_id')) {
    function get_queried_object_id()
    {
        return 42;
    }
}
if (!function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        return $GLOBALS['user_id'] ?? 0;
    }
}
if (!function_exists('utilisateur_peut_modifier_post')) {
    function utilisateur_peut_modifier_post($id)
    {
        return false;
    }
}
if (!function_exists('recuperer_id_chasse_associee')) {
    function recuperer_id_chasse_associee($id)
    {
        return 123;
    }
}
if (!function_exists('verifier_et_synchroniser_cache_enigmes_si_autorise')) {
    function verifier_et_synchroniser_cache_enigmes_si_autorise($chasse_id) {}
}
if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in()
    {
        return $GLOBALS['logged_in'] ?? false;
    }
}
if (!function_exists('get_permalink')) {
    function get_permalink($id)
    {
        return 'permalink-' . $id;
    }
}
if (!function_exists('home_url')) {
    function home_url($path = '/')
    {
        return 'home' . $path;
    }
}
if (!function_exists('wp_redirect')) {
    function wp_redirect($url)
    {
        $GLOBALS['redirect_to'] = $url;
        throw new Exception('redirect');
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/access.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class EnigmeAccessRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['is_singular'] = true;
        $GLOBALS['user_id']     = 0;
        $GLOBALS['logged_in']   = false;
        $GLOBALS['redirect_to'] = null;
    }

    public function test_guest_is_redirected_to_related_chasse(): void
    {
        try {
            handle_single_enigme_access();
        } catch (Exception $e) {
            // Intercepts exit triggered by the handler.
        }
        $this->assertSame('permalink-123', $GLOBALS['redirect_to']);
    }
}
