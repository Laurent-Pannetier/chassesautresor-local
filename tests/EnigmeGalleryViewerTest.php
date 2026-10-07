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
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = null)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_html')) {
    function esc_html($text)
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
        if ($key === 'etape_image') {
            return $GLOBALS['test_step_images'][$id] ?? 0;
        }

        return null;
    }
}
if (!function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        return (int) ($GLOBALS['test_user_id'] ?? 0);
    }
}
if (!function_exists('utilisateur_peut_modifier_post')) {
    function utilisateur_peut_modifier_post($post_id)
    {
        return !empty($GLOBALS['test_can_modify']);
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
        unset(
            $GLOBALS['test_enigme_gallery'],
            $GLOBALS['test_step_images'],
            $GLOBALS['test_user_id'],
            $GLOBALS['test_can_modify'],
            $GLOBALS['test_step_ordered_ids'],
            $GLOBALS['test_step_visible_ids']
        );
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
        $this->assertStringContainsString('enigme-media-zoom__hint', $html);
        $this->assertStringContainsString('taille=full', $html);
        $this->assertStringNotContainsString('galerie-enigme__thumbs', $html);
        $this->assertStringNotContainsString('galerie-enigme__nav', $html);
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
        $this->assertStringContainsString('galerie-enigme__nav--prev', $html);
        $this->assertStringContainsString('galerie-enigme__nav--next', $html);
        $this->assertStringContainsString('Page 1 / 2', $html);
        $this->assertStringContainsString('data-gallery-goto="1"', $html);
        $this->assertStringContainsString('Afficher la page 2', $html);
        $this->assertSame(2, substr_count($html, 'galerie-enigme__slide'));
        $this->assertStringContainsString(' hidden', $html);
        $this->assertStringContainsString('taille=thumbnail', $html);
    }

    public function test_unlocked_step_images_append_as_comic_pages(): void
    {
        $GLOBALS['test_enigme_gallery'] = [
            ['ID' => 101],
        ];

        ob_start();
        afficher_visuels_enigme(8, 9, [
            ['image_id' => 303, 'step_id' => 11],
        ]);
        $html = ob_get_clean();

        $this->assertSame(2, substr_count($html, 'data-gallery-index="'));
        $this->assertStringContainsString('data-gallery-step-id="11"', $html);
        $this->assertStringContainsString('Page 2 / 2', $html);
        $this->assertStringContainsString('galerie-enigme__slide--step', $html);
        $this->assertMatchesRegularExpression(
            '/galerie-enigme__slide--step[^>]*is-active|is-active[^>]*galerie-enigme__slide--step/',
            $html
        );
    }
}
