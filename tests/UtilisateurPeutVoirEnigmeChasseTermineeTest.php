<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

class UtilisateurPeutVoirEnigmeChasseTermineeTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_visuels_accessibles_apres_chasse_terminee(): void
    {
        if (!function_exists('get_post_type')) {
            function get_post_type($id)
            {
                return 'enigme';
            }
        }
        if (!function_exists('get_post_status')) {
            function get_post_status($id)
            {
                return 'publish';
            }
        }
        if (!function_exists('get_current_user_id')) {
            function get_current_user_id()
            {
                return 0;
            }
        }
        if (!function_exists('current_user_can')) {
            function current_user_can($capability)
            {
                return false;
            }
        }
        if (!function_exists('recuperer_id_chasse_associee')) {
            function recuperer_id_chasse_associee($enigme_id)
            {
                return 10;
            }
        }
        if (!function_exists('get_field')) {
            function get_field($field, $post_id = null, $format_value = true)
            {
                if ($field === 'chasse_cache_statut' && $post_id === 10) {
                    return 'termine';
                }
                if ($field === 'enigme_cache_etat_systeme') {
                    return 'terminee';
                }
                return null;
            }
        }
        if (!function_exists('cat_debug')) {
            function cat_debug($message)
            {
                // noop for tests
            }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/access-functions.php';

        $this->assertTrue(utilisateur_peut_voir_enigme(42, null));
    }
}
