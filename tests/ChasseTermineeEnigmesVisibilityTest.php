<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

class ChasseTermineeEnigmesVisibilityTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_masque_enigmes_pour_chasse_terminee_non_engage(): void
    {
        if (!function_exists('get_post_type')) {
            function get_post_type($id) {
                return $id === 1 ? 'chasse' : '';
            }
        }
        if (!function_exists('get_current_user_id')) {
            function get_current_user_id() {
                return 0;
            }
        }
        if (!function_exists('chasse_est_visible_pour_utilisateur')) {
            function chasse_est_visible_pour_utilisateur($chasse_id, $user_id) {
                return true;
            }
        }
        if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
            function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id) {
                return false;
            }
        }
        if (!function_exists('user_can')) {
            function user_can($user_id, $capability) {
                return false;
            }
        }
        if (!function_exists('utilisateur_est_engage_dans_chasse')) {
            function utilisateur_est_engage_dans_chasse($user_id, $chasse_id) {
                return false;
            }
        }
        if (!function_exists('preparer_infos_affichage_chasse')) {
            function preparer_infos_affichage_chasse(int $chasse_id, ?int $user_id = null): array {
                return [
                    'statut'            => 'termine',
                    'statut_validation' => 'valide',
                    'enigmes_associees' => [],
                ];
            }
        }
        if (!function_exists('get_posts')) {
            function get_posts($args) {
                return [];
            }
        }
        if (!function_exists('est_organisateur')) {
            function est_organisateur() {
                return false;
            }
        }
        if (!function_exists('get_post_status')) {
            function get_post_status($id) {
                return 'publish';
            }
        }
        if (!function_exists('utilisateur_peut_ajouter_enigme')) {
            function utilisateur_peut_ajouter_enigme($chasse_id, $user_id) {
                return false;
            }
        }
        if (!function_exists('enigme_compter_joueurs_engages')) {
            function enigme_compter_joueurs_engages($id) {
                return 0;
            }
        }
        if (!function_exists('compter_tentatives_du_jour')) {
            function compter_tentatives_du_jour($user_id, $enigme_id) {
                return 0;
            }
        }
        if (!function_exists('compter_tentatives_en_attente')) {
            function compter_tentatives_en_attente($user_id, $enigme_id) {
                return 0;
            }
        }
        if (!function_exists('get_stylesheet_directory')) {
            function get_stylesheet_directory() {
                return __DIR__ . '/../wp-content/themes/chassesautresor';
            }
        }
        if (!function_exists('esc_attr')) {
            function esc_attr($text) {
                return $text;
            }
        }
        if (!function_exists('esc_html')) {
            function esc_html($text) {
                return $text;
            }
        }

        $args = [
            'chasse_id' => 1,
        ];

        ob_start();
        require __DIR__ . '/../wp-content/themes/chassesautresor/template-parts/enigme/chasse-partial-boucle-enigmes.php';
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }
}

