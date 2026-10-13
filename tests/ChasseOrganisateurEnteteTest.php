<?php

use PHPUnit\Framework\TestCase;

class ChasseOrganisateurEnteteTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_hides_organizer_outside_platform_mode(): void
    {
        $html = $this->renderHuntHeader(false);

        $this->assertStringNotContainsString('chasse-organisateur', $html);
        $this->assertStringNotContainsString('Cats', $html);
        $this->assertStringNotContainsString('présente', $html);
        $this->assertStringContainsString('ANTRE DE FEU', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_shows_organizer_in_platform_mode(): void
    {
        $html = $this->renderHuntHeader(true);

        $this->assertStringContainsString('chasse-organisateur__nom', $html);
        $this->assertStringContainsString('Cats', $html);
        $this->assertStringContainsString('présente', $html);
        $this->assertStringContainsString('ANTRE DE FEU', $html);
    }

    private function renderHuntHeader(bool $platformMode): string
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__);
        }

        $GLOBALS['cat_test_platform_mode'] = $platformMode;

        if (!function_exists('get_post_type')) {
            function get_post_type($id)
            {
                return 'chasse';
            }
        }
        if (!function_exists('get_the_title')) {
            function get_the_title($id)
            {
                return (int) $id === 55 ? 'Cats' : 'ANTRE DE FEU';
            }
        }
        if (!function_exists('get_the_author')) {
            function get_the_author()
            {
                return 'Auteur';
            }
        }
        if (!function_exists('get_permalink')) {
            function get_permalink($id)
            {
                return 'https://example.com/post/' . $id;
            }
        }
        if (!function_exists('formater_date')) {
            function formater_date($date)
            {
                return (string) $date;
            }
        }
        if (!function_exists('current_time')) {
            function current_time($type)
            {
                return 0;
            }
        }
        if (!function_exists('utilisateur_peut_modifier_post')) {
            function utilisateur_peut_modifier_post($id)
            {
                return false;
            }
        }
        if (!function_exists('chasse_est_complet')) {
            function chasse_est_complet($id)
            {
                return true;
            }
        }
        if (!function_exists('get_organisateur_from_chasse')) {
            function get_organisateur_from_chasse($id)
            {
                return 55;
            }
        }
        if (!function_exists('current_user_can')) {
            function current_user_can($cap)
            {
                return false;
            }
        }
        if (!function_exists('cat_is_platform_mode')) {
            function cat_is_platform_mode()
            {
                return !empty($GLOBALS['cat_test_platform_mode']);
            }
        }
        if (!function_exists('get_field')) {
            function get_field($field, $post_id = null, $format = true)
            {
                return $field === 'logo_organisateur' ? 10 : null;
            }
        }
        if (!function_exists('wp_get_attachment_image_src')) {
            function wp_get_attachment_image_src($id, $size)
            {
                return ['https://example.com/logo.jpg', 40, 40];
            }
        }
        if (!function_exists('wp_get_attachment_image_url')) {
            function wp_get_attachment_image_url($id, $size)
            {
                return '';
            }
        }
        if (!function_exists('render_liens_publics')) {
            function render_liens_publics($liens, $type, $opts = [])
            {
                return '';
            }
        }
        if (!function_exists('get_svg_icon')) {
            function get_svg_icon($name)
            {
                return '';
            }
        }
        if (!function_exists('esc_html__')) {
            function esc_html__($text, $domain = null)
            {
                return $text;
            }
        }
        if (!function_exists('esc_attr__')) {
            function esc_attr__($text, $domain = null)
            {
                return $text;
            }
        }
        if (!function_exists('__')) {
            function __($text, $domain = null)
            {
                return $text;
            }
        }
        if (!function_exists('_n')) {
            function _n($single, $plural, $number, $domain = null)
            {
                return ((int) $number === 1) ? $single : $plural;
            }
        }
        if (!function_exists('esc_html')) {
            function esc_html($text)
            {
                return (string) $text;
            }
        }
        if (!function_exists('esc_attr')) {
            function esc_attr($text)
            {
                return (string) $text;
            }
        }
        if (!function_exists('esc_url')) {
            function esc_url($text)
            {
                return (string) $text;
            }
        }
        if (!function_exists('esc_html_e')) {
            function esc_html_e($text, $domain = null)
            {
                echo $text;
            }
        }
        if (!function_exists('esc_attr_e')) {
            function esc_attr_e($text, $domain = null)
            {
                echo $text;
            }
        }
        if (!function_exists('sanitize_text_field')) {
            function sanitize_text_field($text)
            {
                return $text;
            }
        }
        if (!function_exists('esc_url_raw')) {
            function esc_url_raw($url)
            {
                return $url;
            }
        }
        if (!function_exists('wp_kses_post')) {
            function wp_kses_post($content)
            {
                return $content;
            }
        }
        if (!function_exists('get_template_part')) {
            function get_template_part($slug, $name = null, $args = [])
            {
                return '';
            }
        }
        if (!function_exists('number_format_i18n')) {
            function number_format_i18n($number, $decimals = 0)
            {
                return (string) $number;
            }
        }
        if (!function_exists('chasse_preparer_dates_courtes')) {
            function chasse_preparer_dates_courtes($start, $end, $unlimited)
            {
                return [
                    'date_debut_court' => '2 octobre 2025',
                    'date_fin_court' => 'Illimitée',
                ];
            }
        }
        if (!function_exists('chasse_preparer_termes_affichage')) {
            function chasse_preparer_termes_affichage($id, $taxonomy)
            {
                return [];
            }
        }
        if (!function_exists('chasse_format_meta_terms')) {
            function chasse_format_meta_terms($terms)
            {
                return [];
            }
        }

        $args = [
            'chasse_id' => 123,
            'infos_chasse' => [
                'champs' => [
                    'lot' => '',
                    'titre_recompense' => '',
                    'valeur_recompense' => '',
                    'date_debut' => '2025-10-02',
                    'date_fin' => '',
                    'illimitee' => true,
                    'nb_max' => 0,
                    'cout_points' => 0,
                    'date_decouverte' => '',
                    'gagnants' => '',
                    'mode_fin' => 'automatique',
                    'current_stored_statut' => '',
                ],
                'image_raw' => '',
                'image_id' => null,
                'image_url' => '',
                'image_alt' => '',
                'liens' => [],
                'enigmes_associees' => [],
                'total_enigmes' => 2,
                'nb_joueurs' => 2,
                'nb_enigmes_payantes' => 0,
                'top_avances' => ['nb' => 0, 'enigmes' => 0],
                'statut' => 'en_cours',
                'statut_validation' => 'valide',
                'cta_data' => ['cta_message' => '', 'cta_html' => '', 'type' => ''],
                'description' => '',
                'date_debut_court' => '2 octobre 2025',
                'date_fin_court' => 'Illimitée',
                'regions' => [],
                'themes' => [],
            ],
        ];

        ob_start();
        include __DIR__ . '/../wp-content/themes/chassesautresor/template-parts/chasse/chasse-affichage-complet.php';
        return (string) ob_get_clean();
    }
}
