<?php

use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!function_exists('esc_url')) {
    function esc_url($url)
    {
        return $url;
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}
if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = null)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('site_url')) {
    function site_url($path = '')
    {
        return 'https://example.com' . $path;
    }
}
if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url)
    {
        return $url . '?' . http_build_query($args);
    }
}
if (!function_exists('wp_get_attachment_image_url')) {
    function wp_get_attachment_image_url($id, $size = 'full')
    {
        return 'https://example.com/uploads/' . $id . '-' . $size . '.jpg';
    }
}
if (!function_exists('wp_get_attachment_image_src')) {
    function wp_get_attachment_image_src($id, $size = 'full')
    {
        return ['https://example.com/uploads/' . $id . '-' . $size . '.jpg', 800, 600];
    }
}
if (!function_exists('get_post_meta')) {
    function get_post_meta($id, $key, $single = false)
    {
        return '';
    }
}
if (!function_exists('get_intermediate_image_sizes')) {
    function get_intermediate_image_sizes()
    {
        return ['thumbnail', 'medium', 'large'];
    }
}
if (!function_exists('wp_image_editor_supports')) {
    function wp_image_editor_supports($args = [])
    {
        return false;
    }
}
if (!function_exists('utilisateur_peut_voir_enigme')) {
    function utilisateur_peut_voir_enigme($enigme_id)
    {
        return true;
    }
}
if (!function_exists('get_field')) {
    function get_field($key, $id = false, $format = true)
    {
        if ($key === 'enigme_visuel_image') {
            return $GLOBALS['test_enigme_gallery'] ?? [];
        }
        if ($key === 'enigme_visuel_legende') {
            return 'Légende test';
        }

        return null;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/visuels.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class EnigmeGalleryViewerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['test_enigme_gallery']);
        parent::tearDown();
    }

    public function test_single_image_has_lightbox_without_thumbnails(): void
    {
        $GLOBALS['test_enigme_gallery'] = [
            ['ID' => 101],
        ];

        ob_start();
        afficher_visuels_enigme(7);
        $html = ob_get_clean();

        $this->assertStringContainsString('data-enigme-gallery', $html);
        $this->assertStringContainsString('data-enigme-lightbox-src=', $html);
        $this->assertStringContainsString('enigme-media-zoom', $html);
        $this->assertStringContainsString('taille=full', $html);
        $this->assertStringNotContainsString('galerie-enigme__thumbs', $html);
        $this->assertSame(1, substr_count($html, 'galerie-enigme__slide'));
    }

    public function test_multiple_images_render_thumbnails_and_hidden_slides(): void
    {
        $GLOBALS['test_enigme_gallery'] = [
            ['ID' => 101],
            ['ID' => 202],
        ];

        ob_start();
        afficher_visuels_enigme(8);
        $html = ob_get_clean();

        $this->assertStringContainsString('galerie-enigme__thumbs', $html);
        $this->assertStringContainsString('data-gallery-goto="1"', $html);
        $this->assertSame(2, substr_count($html, 'galerie-enigme__slide'));
        $this->assertStringContainsString(' hidden', $html);
        $this->assertStringContainsString('taille=thumbnail', $html);
    }
}
