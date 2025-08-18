<?php
use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!function_exists('get_field')) {
    function get_field($key, $post_id)
    {
        return $GLOBALS['fields'][$key] ?? null;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($role)
    {
        return $GLOBALS['is_admin'] ?? false;
    }
}

if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
    function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id)
    {
        return $GLOBALS['is_associated'] ?? false;
    }
}

if (!function_exists('est_organisateur')) {
    function est_organisateur($user_id = null)
    {
        return $GLOBALS['is_organizer'] ?? false;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/affichage.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class EnigmeMenuVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['fields'] = [
            'chasse_cache_statut_validation' => 'valide',
        ];
        $GLOBALS['is_admin'] = false;
        $GLOBALS['is_associated'] = false;
        $GLOBALS['is_organizer'] = false;
    }

    public function test_menu_visible_for_associated_organizer(): void
    {
        $GLOBALS['is_associated'] = true;
        $GLOBALS['is_organizer'] = true;

        $this->assertTrue(enigme_user_can_see_menu(1, 2, 'revision'));
    }

    public function test_menu_hidden_for_other_roles_in_revision(): void
    {
        $this->assertFalse(enigme_user_can_see_menu(1, 2, 'revision'));
    }
}
