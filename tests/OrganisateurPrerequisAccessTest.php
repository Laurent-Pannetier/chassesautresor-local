<?php
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class OrganisateurPrerequisAccessTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/access-functions.php';
    }

    public function test_organisateur_peut_voir_enigme_bloquee_par_prerequis(): void
    {
        global $fields, $enigme_chasse, $post_status;
        $enigme_id = 1;
        $chasse_id = 2;
        $enigme_chasse = [$enigme_id => $chasse_id];
        $post_status = [$enigme_id => 'publish'];
        $fields = [
            $enigme_id => ['enigme_cache_etat_systeme' => 'bloquee_pre_requis'],
            $chasse_id => ['chasse_cache_statut_validation' => 'active'],
        ];
        $this->assertTrue(utilisateur_peut_voir_enigme($enigme_id));
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($id)
    {
        return 'enigme';
    }
}
if (!function_exists('get_post_status')) {
    function get_post_status($id)
    {
        global $post_status;
        return $post_status[$id] ?? 'publish';
    }
}
if (!function_exists('get_field')) {
    function get_field($field, $post_id)
    {
        global $fields;
        return $fields[$post_id][$field] ?? null;
    }
}
if (!function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        return 1;
    }
}
if (!function_exists('recuperer_id_chasse_associee')) {
    function recuperer_id_chasse_associee($enigme_id)
    {
        global $enigme_chasse;
        return $enigme_chasse[$enigme_id] ?? null;
    }
}
if (!function_exists('utilisateur_est_engage_dans_chasse')) {
    function utilisateur_est_engage_dans_chasse($user_id, $chasse_id)
    {
        return false;
    }
}
if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in()
    {
        return true;
    }
}
if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user()
    {
        return (object) ['roles' => ['organisateur']];
    }
}
if (!function_exists('current_user_can')) {
    function current_user_can($cap)
    {
        return false;
    }
}
if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
    function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id)
    {
        return true;
    }
}
if (!function_exists('cat_debug')) {
    function cat_debug(...$args): void
    {
    }
}
