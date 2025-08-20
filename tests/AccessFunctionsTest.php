<?php
use PHPUnit\Framework\TestCase;

if (!defined('ROLE_ORGANISATEUR')) {
    define('ROLE_ORGANISATEUR', 'organisateur');
}
if (!defined('ROLE_ORGANISATEUR_CREATION')) {
    define('ROLE_ORGANISATEUR_CREATION', 'organisateur_en_creation');
}

if (!function_exists('get_post_type')) {
    function get_post_type($id) {
        return 'chasse';
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 10;
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        return true;
    }
}

if (!function_exists('get_userdata')) {
    function get_userdata($user_id) {
        return (object) ['roles' => [ROLE_ORGANISATEUR]];
    }
}


if (!function_exists('get_field')) {
    function get_field($field, $post_id) {
        global $fields, $post_fields;
        return $fields[$post_id][$field] ?? $post_fields[$post_id][$field] ?? null;
    }
}

if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
    function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id) {
        return true;
    }
}

if (!function_exists('recuperer_ids_enigmes_pour_chasse')) {
    function recuperer_ids_enigmes_pour_chasse($chasse_id) {
        global $enigme_ids;
        return $enigme_ids ?? [];
    }
}

if (!function_exists('get_post_status')) {
    function get_post_status($id) {
        global $statuses;
        return $statuses[$id] ?? 'publish';
    }
}

if (!function_exists('cat_debug')) {
    function cat_debug(...$args): void {}
}

if (!function_exists('get_post')) {
    function get_post($post_id) {
        global $posts;
        return $posts[$post_id] ?? null;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/access-functions.php';

class AccessFunctionsTest extends TestCase {
    public function test_utilisateur_ne_peut_pas_ajouter_enigme_chasse_publiee(): void {
        global $fields, $statuses, $enigme_ids;
        $chasse_id = 100;
        $fields = [
            $chasse_id => [
                'chasse_cache_statut_validation' => 'creation',
                'chasse_cache_statut' => 'revision',
            ],
        ];
        $statuses   = [$chasse_id => 'publish'];
        $enigme_ids = [1];
        $this->assertFalse(utilisateur_peut_ajouter_enigme($chasse_id));
    }

    public function test_utilisateur_peut_ajouter_enigme_chasse_en_revision(): void {
        global $fields, $statuses, $enigme_ids;
        $chasse_id = 101;
        $fields = [
            $chasse_id => [
                'chasse_cache_statut_validation' => 'creation',
                'chasse_cache_statut' => 'revision',
            ],
        ];
        $statuses   = [$chasse_id => 'pending'];
        $enigme_ids = [1];
        $this->assertTrue(utilisateur_peut_ajouter_enigme($chasse_id));
    }

    /**
     * @runInSeparateProcess
     */
    public function test_utilisateur_est_auteur_du_organisateur_associe(): void {
        global $fields, $posts;
        $user_id = 10;
        $chasse_id = 102;
        $organisateur_id = 202;
        $fields = [
            $chasse_id => [
                'chasse_cache_organisateur' => $organisateur_id,
            ],
            $organisateur_id => [
                'utilisateurs_associes' => [],
            ],
        ];
        $posts = [
            $organisateur_id => (object) ['ID' => $organisateur_id, 'post_author' => $user_id],
        ];

        $code = file_get_contents(__DIR__ . '/../wp-content/themes/chassesautresor/inc/relations-functions.php');
        $code = preg_replace('/^<\?php/', '', $code);
        eval('namespace RelationsReal {' . $code . '}');

        $this->assertTrue(\RelationsReal\utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id));
    }
}
