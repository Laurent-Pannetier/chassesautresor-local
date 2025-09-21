<?php

use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}

if (!function_exists('add_action')) {
    function add_action(...$args): void {}
}

if (!function_exists('add_filter')) {
    function add_filter(...$args): void {}
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value)
    {
        return $value;
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key)
    {
        $key = strtolower((string) $key);
        return preg_replace('/[^a-z0-9_\-]/', '', $key);
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = [])
    {
        if (is_array($args)) {
            return array_merge($defaults, $args);
        }

        if (is_object($args)) {
            return array_merge($defaults, get_object_vars($args));
        }

        parse_str((string) $args, $parsed);

        return array_merge($defaults, $parsed);
    }
}

if (!function_exists('_doing_it_wrong')) {
    function _doing_it_wrong(...$args): void {}
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default')
    {
        return $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return (string) $text;
    }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = 'default')
    {
        echo esc_html($text);
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return $text;
    }
}

if (!function_exists('_n')) {
    function _n($single, $plural, $number, $domain = 'default')
    {
        return (int) $number > 1 ? $plural : $single;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return (string) $text;
    }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = 'default')
    {
        return $text;
    }
}

if (!function_exists('esc_attr_e')) {
    function esc_attr_e($text, $domain = 'default')
    {
        echo esc_attr($text);
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url)
    {
        return (string) $url;
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in()
    {
        return true;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        return 42;
    }
}

if (!function_exists('get_stylesheet_directory')) {
    function get_stylesheet_directory()
    {
        $path = realpath(__DIR__ . '/../wp-content/themes/chassesautresor');

        return $path !== false ? $path : __DIR__ . '/../wp-content/themes/chassesautresor';
    }
}

if (!function_exists('get_stylesheet_directory_uri')) {
    function get_stylesheet_directory_uri()
    {
        return 'https://example.com/theme';
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(...$args): void {}
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n)
    {
        return true;
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin')
    {
        $base = 'https://example.com/wp-admin/';

        return $base . ltrim((string) $path, '/');
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return $value;
    }
}

if (!function_exists('wp_doing_ajax')) {
    function wp_doing_ajax()
    {
        return false;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value)
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($cap = '')
    {
        return true;
    }
}

if (!function_exists('cta_render_search_form')) {
    function cta_render_search_form($key, $overrides = [])
    {
        return '<form class="table-search" data-search-key="' . esc_attr($key) . '" data-ajax-action="ca_fetch_tentatives" data-ajax-target="#tentatives-table-wrapper">'
            . '<div class="table-search__controls">'
            . '<button type="button" class="table-search__reset" data-table-search-reset hidden></button>'
            . '</div>'
            . '</form>';
    }
}

if (!function_exists('cta_render_proposition_cell')) {
    function cta_render_proposition_cell($text, $expanded = false, $limit = 39)
    {
        return '<td class="proposition-cell">' . esc_html($text) . '</td>';
    }
}

if (!function_exists('cta_render_pager')) {
    function cta_render_pager($current, $total, $class = '', $attributes = [])
    {
        return '<nav class="' . esc_attr($class) . '"></nav>';
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post_id)
    {
        return 'https://example.com/post-' . (int) $post_id;
    }
}

if (!function_exists('mysql2date')) {
    function mysql2date($format, $date)
    {
        $datetime = date_create($date);
        if (!$datetime) {
            return '';
        }

        return $datetime->format($format);
    }
}

class TentativesTestWpdb
{
    public string $prefix = 'wp_';
    public string $posts = 'wp_posts';
    public string $postmeta = 'wp_postmeta';
    public string $last_get_results_sql = '';

    public function esc_like($text)
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $text);
    }

    public function prepare($query, ...$args)
    {
        if (count($args) === 1 && is_array($args[0])) {
            $args = $args[0];
        }

        $segments = preg_split('/(%s|%d|%f)/', $query, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result   = '';
        $index    = 0;

        foreach ($segments as $segment) {
            if ($segment === '%s') {
                $value  = $args[$index++] ?? '';
                $result .= "'" . addslashes((string) $value) . "'";
            } elseif ($segment === '%d') {
                $value  = $args[$index++] ?? 0;
                $result .= (string) (int) $value;
            } elseif ($segment === '%f') {
                $value  = $args[$index++] ?? 0.0;
                $result .= (string) (float) $value;
            } else {
                $result .= $segment;
            }
        }

        return $result;
    }

    public function get_var($sql)
    {
        if (strpos($sql, "resultat = 'attente'") !== false) {
            return 1;
        }

        if (strpos($sql, "resultat = 'bon'") !== false && strpos($sql, 'chasses') === false) {
            return 2;
        }

        if (strpos($sql, 'COUNT(*)') !== false && strpos($sql, 'chasses') !== false) {
            return stripos($sql, 'bonbons') !== false ? 1 : 3;
        }

        if (strpos($sql, 'COUNT(*)') !== false) {
            return 3;
        }

        return 0;
    }

    public function get_results($sql, $output = OBJECT)
    {
        $this->last_get_results_sql = $sql;
        $rows = [
            (object) [
                'ID'             => 1,
                'enigme_id'      => 10,
                'date_tentative' => '2024-05-01 10:00:00',
                'reponse_saisie' => 'Bonbons',
                'resultat'       => 'bon',
                'chasse_id'      => 201,
                'chasse_title'   => 'Chasse aux bonbons',
                'enigme_title'   => 'Énigme Bonbons',
            ],
            (object) [
                'ID'             => 2,
                'enigme_id'      => 11,
                'date_tentative' => '2024-05-02 11:00:00',
                'reponse_saisie' => 'Pirates',
                'resultat'       => 'mauvais',
                'chasse_id'      => 202,
                'chasse_title'   => 'Chasse aux pirates',
                'enigme_title'   => 'Énigme Pirates',
            ],
        ];

        if (stripos($sql, 'bonbons') !== false) {
            return [$rows[0]];
        }

        return $rows;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/search/registry.php';
require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/search/helpers.php';
require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/user-functions.php';

class MyAccountTentativesTest extends TestCase
{
    protected function setUp(): void
    {
        global $wpdb;
        $wpdb = new TentativesTestWpdb();

        $registry = &ca_get_search_registry_storage();
        $registry = [];

        $_GET = [];
    }

    public function test_search_filters_results(): void
    {
        global $wpdb;

        $_GET['search'] = [
            'context'    => 'tentatives',
            'tentatives' => 'bonbons',
        ];

        ob_start();
        ca_render_dashboard_tentatives();
        $output = ob_get_clean();

        $this->assertStringContainsString('Chasse aux bonbons', $output);
        $this->assertStringNotContainsString('Chasse aux pirates', $output);
        $this->assertStringContainsString('table-search', $output);
        $this->assertStringContainsString('table-search__reset', $output);
        $this->assertStringContainsString('data-ajax-action="ca_fetch_tentatives"', $output);
        $this->assertStringContainsString('id="tentatives-table-wrapper"', $output);
    }
}
