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

if (!function_exists('update_field')) {
    function update_field($key, $value, $post_id = null)
    {
        if ($post_id !== null) {
            $GLOBALS['fields'][$post_id][$key] = $value;
        } else {
            $GLOBALS['fields'][$key] = $value;
        }
        return true;
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

if (!function_exists('remove_accents')) {
    function remove_accents($string)
    {
        return $string;
    }
}

if (!function_exists('wp_date')) {
    function wp_date($format, $timestamp)
    {
        return date($format, $timestamp);
    }
}

if (!function_exists('compter_tentatives_du_jour')) {
    function compter_tentatives_du_jour($uid, $enigme_id)
    {
        return 0;
    }
}

if (!function_exists('get_user_points')) {
    function get_user_points($user_id)
    {
        return 100;
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($id)
    {
        return $GLOBALS['titles'][$id] ?? 'Title';
    }
}

if (!function_exists('titre_est_valide')) {
    function titre_est_valide($id)
    {
        return true;
    }
}

if (!function_exists('utilisateur_est_engage_dans_chasse')) {
    function utilisateur_est_engage_dans_chasse($user_id, $chasse_id)
    {
        return $GLOBALS['engage_chasse'] ?? true;
    }
}

if (!function_exists('utilisateur_est_engage_dans_enigme')) {
    function utilisateur_est_engage_dans_enigme($user_id, $post_id)
    {
        return $GLOBALS['engage_enigme'] ?? false;
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

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/layout-functions.php';
require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/statut-functions.php';
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
        $GLOBALS['engage_chasse'] = true;
        $GLOBALS['engage_enigme'] = false;

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

    public function test_menu_excludes_prerequisite_locked_enigme_for_user(): void
    {
        $GLOBALS['is_admin']      = false;
        $GLOBALS['is_associated'] = false;
        $GLOBALS['is_organizer']  = false;
        $GLOBALS['fields'][2]['chasse_cache_statut'] = 'ouverte';

        $GLOBALS['fields'][101]['enigme_cache_complet']       = true;
        $GLOBALS['fields'][101]['enigme_cache_etat_systeme']  = 'accessible';
        $GLOBALS['fields'][101]['enigme_acces_condition']     = 'immediat';
        $GLOBALS['post_status'][101] = 'publish';

        $GLOBALS['fields'][102] = [
            'enigme_cache_complet'       => true,
            'enigme_cache_etat_systeme'  => 'bloquee_pre_requis',
            'enigme_acces_condition'     => 'pre_requis',
            'enigme_acces_pre_requis'    => [201],
        ];
        $GLOBALS['post_types'][102]  = 'enigme';
        $GLOBALS['post_status'][102] = 'publish';
        $GLOBALS['titles'][102]      = 'Enigme Bloquee';
        $GLOBALS['enigma_list']      = [(object) ['ID' => 101], (object) ['ID' => 102]];

        ob_start();
        afficher_enigme_stylisee(101);
        $output = ob_get_clean();
        $this->assertStringNotContainsString('data-enigme-id="102"', $output);
    }

    public function test_menu_excludes_enigme_with_empty_prerequisites(): void
    {
        $GLOBALS['is_admin']      = false;
        $GLOBALS['is_associated'] = false;
        $GLOBALS['is_organizer']  = false;
        $GLOBALS['fields'][2]['chasse_cache_statut'] = 'ouverte';

        $GLOBALS['fields'][101]['enigme_cache_complet']       = true;
        $GLOBALS['fields'][101]['enigme_cache_etat_systeme']  = 'accessible';
        $GLOBALS['fields'][101]['enigme_acces_condition']     = 'immediat';
        $GLOBALS['post_status'][101] = 'publish';

        $GLOBALS['fields'][102] = [
            'enigme_cache_complet'       => true,
            'enigme_cache_etat_systeme'  => 'bloquee_pre_requis',
            'enigme_acces_condition'     => 'pre_requis',
            'enigme_acces_pre_requis'    => [],
        ];
        $GLOBALS['post_types'][102]  = 'enigme';
        $GLOBALS['post_status'][102] = 'publish';
        $GLOBALS['titles'][102]      = 'Enigme Mal Config';
        $GLOBALS['enigma_list']      = [(object) ['ID' => 101], (object) ['ID' => 102]];

        ob_start();
        afficher_enigme_stylisee(101);
        $output = ob_get_clean();
        $this->assertStringNotContainsString('data-enigme-id="102"', $output);
    }

    public function test_menu_excludes_date_locked_enigme_for_user(): void
    {
        $GLOBALS['is_admin']      = false;
        $GLOBALS['is_associated'] = false;
        $GLOBALS['is_organizer']  = false;
        $GLOBALS['fields'][2]['chasse_cache_statut'] = 'ouverte';

        $GLOBALS['fields'][101] = [
            'enigme_cache_complet'       => true,
            'enigme_cache_etat_systeme'  => 'accessible',
            'enigme_acces_condition'     => 'immediat',
        ];
        $GLOBALS['post_types'][101]  = 'enigme';
        $GLOBALS['post_status'][101] = 'publish';
        $GLOBALS['titles'][101]      = 'Enigme Accessible';

        $GLOBALS['fields'][102] = [
            'enigme_cache_complet'       => true,
            'enigme_cache_etat_systeme'  => 'bloquee_date',
            'enigme_acces_condition'     => 'date_programmee',
        ];
        $GLOBALS['post_types'][102]  = 'enigme';
        $GLOBALS['post_status'][102] = 'publish';
        $GLOBALS['titles'][102]      = 'Enigme Future';
        $GLOBALS['enigma_list']      = [(object) ['ID' => 101], (object) ['ID' => 102]];

        ob_start();
        afficher_enigme_stylisee(101);
        $output = ob_get_clean();
        $this->assertStringNotContainsString('data-enigme-id="102"', $output);
    }

    public function test_traiter_statut_enigme_blocks_access_without_prerequisites(): void
    {
        $GLOBALS['is_admin']      = false;
        $GLOBALS['is_associated'] = false;
        $GLOBALS['is_organizer']  = false;
        $GLOBALS['fields'][2]['chasse_cache_statut'] = 'ouverte';
        $GLOBALS['fields'][102] = [
            'enigme_cache_complet'       => true,
            'enigme_cache_etat_systeme'  => 'bloquee_pre_requis',
            'enigme_acces_condition'     => 'pre_requis',
            'enigme_acces_pre_requis'    => [201],
        ];
        $GLOBALS['post_types'][102]  = 'enigme';
        $GLOBALS['post_status'][102] = 'publish';
        $GLOBALS['engage_chasse']    = true;
        $GLOBALS['engage_enigme']    = true;

        $result = traiter_statut_enigme(102, 1);
        $this->assertTrue($result['rediriger']);
        $this->assertSame('bloquee_pre_requis', $result['etat']);
    }

    public function test_participation_section_shown_for_regular_user(): void
    {
        $GLOBALS['is_admin']      = false;
        $GLOBALS['is_associated'] = false;
        $GLOBALS['is_organizer']  = false;
        $GLOBALS['fields'][101]['enigme_cache_complet']       = true;
        $GLOBALS['fields'][101]['indices']                    = ['hint'];
        $GLOBALS['fields'][101]['enigme_mode_validation']     = 'automatique';
        $GLOBALS['fields'][101]['enigme_tentative_cout_points'] = 0;
        $GLOBALS['fields'][101]['enigme_tentative_max']       = 5;

        ob_start();
        afficher_enigme_stylisee(101);
        $output = ob_get_clean();
        $this->assertStringContainsString('<section class="participation">', $output);
    }

    public function test_participation_section_hidden_for_associated_organizer(): void
    {
        $GLOBALS['is_admin']      = false;
        $GLOBALS['is_associated'] = true;
        $GLOBALS['is_organizer']  = true;
        $GLOBALS['fields'][101]['enigme_cache_complet']       = true;
        $GLOBALS['fields'][101]['indices']                    = ['hint'];
        $GLOBALS['fields'][101]['enigme_mode_validation']     = 'automatique';
        $GLOBALS['fields'][101]['enigme_tentative_cout_points'] = 0;
        $GLOBALS['fields'][101]['enigme_tentative_max']       = 5;

        ob_start();
        afficher_enigme_stylisee(101);
        $output = ob_get_clean();
        $this->assertStringNotContainsString('<section class="participation">', $output);
    }
}
