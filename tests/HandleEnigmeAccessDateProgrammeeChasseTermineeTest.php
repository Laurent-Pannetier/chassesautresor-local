<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!function_exists('is_singular')) {
    function is_singular($type) {
        return true;
    }
}
if (!function_exists('get_queried_object_id')) {
    function get_queried_object_id() {
        return 42;
    }
}
if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}
if (!function_exists('utilisateur_peut_modifier_post')) {
    function utilisateur_peut_modifier_post($id) {
        return false;
    }
}
if (!function_exists('recuperer_id_chasse_associee')) {
    function recuperer_id_chasse_associee($id) {
        return 123;
    }
}
if (!function_exists('verifier_et_synchroniser_cache_enigmes_si_autorise')) {
    function verifier_et_synchroniser_cache_enigmes_si_autorise($chasse_id): void {}
}
if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        return true;
    }
}
if (!function_exists('utilisateur_est_engage_dans_chasse')) {
    function utilisateur_est_engage_dans_chasse($user_id, $chasse_id) {
        return true;
    }
}
if (!function_exists('utilisateur_est_engage_dans_enigme')) {
    function utilisateur_est_engage_dans_enigme($user_id, $enigme_id) {
        return false;
    }
}
if (!function_exists('utilisateur_peut_engager_enigme')) {
    function utilisateur_peut_engager_enigme($enigme_id, $user_id) {
        return true;
    }
}
if (!function_exists('marquer_enigme_comme_engagee')) {
    function marquer_enigme_comme_engagee($user_id, $enigme_id): void {}
}
if (!function_exists('get_field')) {
    function get_field($field, $id = null, $format_value = true) {
        if ($field === 'enigme_mode_validation') {
            return 'aucune';
        }
        if ($field === 'chasse_cache_statut' && $id === 123) {
            return 'termine';
        }
        if ($field === 'enigme_cache_etat_systeme') {
            return 'bloquee_date';
        }
        if ($field === 'enigme_acces_condition') {
            return 'date_programmee';
        }
        if ($field === 'enigme_cache_complet') {
            return true;
        }
        return null;
    }
}
if (!function_exists('verifier_fin_de_chasse')) {
    function verifier_fin_de_chasse($user_id, $enigme_id): void {}
}
if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect($url) {
        $GLOBALS['redirect_to'] = $url;
        throw new Exception('redirect');
    }
}
if (!function_exists('wp_redirect')) {
    function wp_redirect($url) {
        $GLOBALS['redirect_to'] = $url;
        throw new Exception('redirect');
    }
}
if (!function_exists('enigme_est_visible_pour')) {
    function enigme_est_visible_pour($user_id, $enigme_id) {
        return true;
    }
}
if (!function_exists('utilisateur_peut_modifier_enigme')) {
    function utilisateur_peut_modifier_enigme($enigme_id) {
        return false;
    }
}
if (!function_exists('verifier_ou_mettre_a_jour_cache_complet')) {
    function verifier_ou_mettre_a_jour_cache_complet($enigme_id): void {}
}
if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
    function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id) {
        return false;
    }
}
if (!function_exists('compter_tentatives_en_attente')) {
    function compter_tentatives_en_attente($enigme_id) {
        return 0;
    }
}
if (!function_exists('get_permalink')) {
    function get_permalink($id) {
        return 'permalink-' . $id;
    }
}
if (!function_exists('home_url')) {
    function home_url($path = '/') {
        return 'home' . $path;
    }
}
if (!function_exists('add_query_arg')) {
    function add_query_arg($key, $value, $url = '') {
        return $url . '?' . $key . '=' . $value;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/access.php';

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class HandleEnigmeAccessDateProgrammeeChasseTermineeTest extends TestCase
{
    public function test_acces_date_programmee_apres_chasse_terminee_ne_redirige_pas(): void
    {
        $GLOBALS['redirect_to'] = null;
        try {
            handle_single_enigme_access();
        } catch (Exception $e) {
            // capture redirect
        }
        $this->assertNull($GLOBALS['redirect_to']);
    }
}

