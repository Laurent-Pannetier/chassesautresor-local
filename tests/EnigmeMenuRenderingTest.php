<?php
use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

if (!function_exists('get_option')) {
    function get_option($name, $default = false)
    {
        return $GLOBALS['options'][$name] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($name, $value)
    {
        $GLOBALS['options'][$name] = $value;
        return true;
    }
}

if (!function_exists('wp_cache_delete')) {
    function wp_cache_delete($key, $group)
    {
    }
}

if (!function_exists('get_field')) {
    function get_field($key, $post_id = null)
    {
        $fields = $GLOBALS['fields'] ?? [];
        if ($post_id !== null && isset($fields[$post_id][$key])) {
            return $fields[$post_id][$key];
        }
        return $fields[$key] ?? null;
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

if (!function_exists('get_post_type')) {
    function get_post_type($id)
    {
        return $GLOBALS['post_types'][$id] ?? '';
    }
}

if (!function_exists('utilisateur_peut_modifier_enigme')) {
    function utilisateur_peut_modifier_enigme($id)
    {
        return false;
    }
}

if (!function_exists('recuperer_id_chasse_associee')) {
    function recuperer_id_chasse_associee($enigme_id)
    {
        return 2;
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($id)
    {
        return '#';
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '/')
    {
        return '#';
    }
}

if (!function_exists('recuperer_ids_enigmes_pour_chasse')) {
    function recuperer_ids_enigmes_pour_chasse($chasse_id)
    {
        return [101];
    }
}

if (!function_exists('chasse_calculer_taux_engagement')) {
    function chasse_calculer_taux_engagement($chasse_id)
    {
        return 0;
    }
}

if (!function_exists('chasse_calculer_taux_progression')) {
    function chasse_calculer_taux_progression($chasse_id)
    {
        return 0;
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect($url)
    {
        $GLOBALS['redirected_to'] = $url;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        return 1;
    }
}

if (!function_exists('utilisateur_peut_modifier_post')) {
    function utilisateur_peut_modifier_post($id)
    {
        return false;
    }
}

if (!function_exists('wp_cache_get')) {
    function wp_cache_get($key, $group)
    {
        return false;
    }
}

if (!function_exists('wp_cache_set')) {
    function wp_cache_set($key, $value, $group, $expire)
    {
    }
}

if (!function_exists('recuperer_enigmes_pour_chasse')) {
    function recuperer_enigmes_pour_chasse($chasse_id)
    {
        return $GLOBALS['enigma_list'] ?? [];
    }
}

if (!function_exists('recuperer_ids_enigmes_pour_chasse')) {
    function recuperer_ids_enigmes_pour_chasse($chasse_id)
    {
        return array_map(static fn($e) => $e->ID, $GLOBALS['enigma_list'] ?? []);
    }
}

if (!function_exists('get_post_status')) {
    function get_post_status($id)
    {
        return $GLOBALS['post_status'][$id] ?? 'draft';
    }
}

if (!function_exists('get_the_post_thumbnail')) {
    function get_the_post_thumbnail($id, $size)
    {
        return '';
    }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain)
    {
        return $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain)
    {
        return $text;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return $text;
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url)
    {
        return $url;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return $text;
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($id)
    {
        return $GLOBALS['titles'][$id] ?? 'Title';
    }
}

if (!function_exists('utilisateur_est_engage_dans_enigme')) {
    function utilisateur_est_engage_dans_enigme($user_id, $post_id)
    {
        return false;
    }
}

if (!function_exists('enigme_get_statut_utilisateur')) {
    function enigme_get_statut_utilisateur($post_id, $user_id)
    {
        return 'non_commencee';
    }
}

if (!function_exists('locate_template')) {
    function locate_template($template)
    {
        return false;
    }
}

if (!function_exists('get_template_part')) {
    function get_template_part($slug, $name = null, $args = [])
    {
    }
}

if (!function_exists('chasse_calculer_taux_engagement')) {
    function chasse_calculer_taux_engagement($chasse_id)
    {
        return 0;
    }
}

if (!function_exists('chasse_calculer_taux_progression')) {
    function chasse_calculer_taux_progression($chasse_id)
    {
        return 0;
    }
}

if (!function_exists('cat_debug')) {
    function cat_debug($message)
    {
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/affichage.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class EnigmeMenuRenderingTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wpdb'] = new class {
            public string $prefix = 'wp_';
            public function prepare($query, ...$args) { return $query; }
            public function get_var($query) { return 0; }
        };
        $GLOBALS['fields'] = [
            2 => [
                'chasse_cache_statut' => 'revision',
                'chasse_cache_statut_validation' => 'valide',
            ],
            101 => [
                'enigme_cache_complet' => false,
                'enigme_cache_etat_systeme' => 'accessible',
            ],
        ];
        $GLOBALS['post_types'] = [101 => 'enigme', 2 => 'chasse'];
        $GLOBALS['post_status'] = [101 => 'draft'];
        $GLOBALS['titles'] = [2 => 'Chasse Test', 101 => 'Enigme Test'];
        $GLOBALS['enigma_list'] = [(object) ['ID' => 101]];
        $GLOBALS['is_admin'] = false;
        $GLOBALS['is_associated'] = true;
        $GLOBALS['is_organizer'] = true;

        global $wpdb;
        $wpdb = new class {
            public string $prefix = 'wp_';

            public function prepare($query, ...$args)
            {
                return $query;
            }

            public function get_var($sql)
            {
                return 0;
            }
        };
    }

    public function test_menu_rendered_for_draft_enigme_for_associated_organizer(): void
    {
        ob_start();
        afficher_enigme_stylisee(101);
        $output = ob_get_clean();
        $this->assertStringContainsString('enigme-menu', $output);
    }
}
