<?php
use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return $url;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return $text;
    }
}

if (!function_exists('site_url')) {
    function site_url($path = '') {
        return 'https://example.com' . $path;
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($key, $value = null, $url = null) {
        if (is_array($key)) {
            return $url . '?' . http_build_query($key);
        }
        return $url . '?' . urlencode($key) . '=' . urlencode($value);
    }
}

if (!function_exists('get_field')) {
    function get_field($key, $id, $format = true) {
        return [
            ['ID' => 1],
            ['ID' => 2],
            ['ID' => 3],
        ];
    }
}

if (!function_exists('utilisateur_peut_voir_enigme')) {
    function utilisateur_peut_voir_enigme($id) {
        return true;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = null) {
        return $text;
    }
}

if (!function_exists('get_stylesheet_directory')) {
    function get_stylesheet_directory() {
        return __DIR__;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/visuels.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class EnigmeVignettesLazyLoadingTest extends TestCase
{
    public function test_vignette_images_use_lazy_loading(): void
    {
        ob_start();
        afficher_visuels_enigme(42);
        $html = ob_get_clean();

        $start = strpos($html, '<div class="galerie-vignettes">');
        $end = strpos($html, '</div>', $start);
        $vignettes_html = substr($html, $start, $end - $start);

        $img_count = substr_count($vignettes_html, '<img');
        $lazy_count = substr_count($vignettes_html, 'loading="lazy"');

        $this->assertSame(3, $img_count);
        $this->assertSame($img_count, $lazy_count);
    }
}
