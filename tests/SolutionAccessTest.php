<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SolutionAccessTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_anonymous_can_view_solution_when_hunt_finished(): void
    {
        if (!function_exists('solution_recuperer_par_objet')) {
            function solution_recuperer_par_objet(int $id, string $type) {
                return (object) ['ID' => 99];
            }
        }
        if (!function_exists('get_field')) {
            function get_field($key, $post_id) {
                return $key === 'chasse_cache_statut' ? 'termine' : null;
            }
        }
        if (!function_exists('user_can')) {
            function user_can($user_id, $capability) { return false; }
        }
        if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
            function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id) { return false; }
        }
        if (!function_exists('utilisateur_est_engage_dans_chasse')) {
            function utilisateur_est_engage_dans_chasse($user_id, $chasse_id) { return false; }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/access-functions.php';

        $this->assertTrue(utilisateur_peut_voir_solution_chasse(1, 0));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_anonymous_cannot_view_solution_before_end(): void
    {
        if (!function_exists('solution_recuperer_par_objet')) {
            function solution_recuperer_par_objet(int $id, string $type) {
                return (object) ['ID' => 99];
            }
        }
        if (!function_exists('get_field')) {
            function get_field($key, $post_id) {
                return $key === 'chasse_cache_statut' ? 'en_cours' : null;
            }
        }
        if (!function_exists('user_can')) {
            function user_can($user_id, $capability) { return false; }
        }
        if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
            function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id) { return false; }
        }
        if (!function_exists('utilisateur_est_engage_dans_chasse')) {
            function utilisateur_est_engage_dans_chasse($user_id, $chasse_id) { return false; }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/access-functions.php';

        $this->assertFalse(utilisateur_peut_voir_solution_chasse(1, 0));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_player_can_view_enigme_solution_after_hunt_ended(): void
    {
        if (!function_exists('solution_recuperer_par_objet')) {
            function solution_recuperer_par_objet(int $id, string $type) {
                return (object) ['ID' => 123];
            }
        }
        if (!function_exists('get_field')) {
            function get_field($key, $post_id) {
                return $key === 'chasse_cache_statut' ? 'termine' : null;
            }
        }
        if (!function_exists('user_can')) {
            function user_can($user_id, $capability) { return false; }
        }
        if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
            function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id) { return false; }
        }
        if (!function_exists('utilisateur_est_engage_dans_enigme')) {
            function utilisateur_est_engage_dans_enigme($user_id, $enigme_id) { return true; }
        }
        if (!function_exists('recuperer_id_chasse_associee')) {
            function recuperer_id_chasse_associee($enigme_id) { return 10; }
        }
        if (!function_exists('get_post_type')) {
            function get_post_type($id) { return 'enigme'; }
        }
        if (!function_exists('get_statut_utilisateur_enigme')) {
            function get_statut_utilisateur_enigme($user_id, $enigme_id) { return 'en_cours'; }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/access-functions.php';

        $this->assertTrue(utilisateur_peut_voir_solution_enigme(2, 1));
    }
}
