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
        $this->assertStringContainsString('data-webtoon', $html);
        $this->assertStringNotContainsString('data-comic-reader', $html);
        $this->assertStringContainsString('min(100vw, 1380px)', $html);
        $this->assertStringContainsString('data-enigme-lightbox-src=', $html);
        $this->assertStringContainsString('enigme-media-zoom', $html);
        $this->assertStringContainsString('enigme-media-zoom__hint', $html);
        $this->assertStringContainsString('101-full.jpg', $html);
        $this->assertStringNotContainsString('voir-image-enigme', $html);
        $this->assertStringNotContainsString('galerie-enigme__thumbs', $html);
        $this->assertStringNotContainsString('galerie-enigme__nav', $html);
        $this->assertStringContainsString('class="galerie-enigme__home" href="/"', $html);
        $this->assertStringContainsString('Accueil', $html);
        $this->assertSame(1, substr_count($html, 'galerie-enigme__slide'));
    }

    public function test_multiple_images_stack_without_a_gap_or_page_chrome(): void
    {
        $GLOBALS['test_enigme_gallery'] = [
            ['ID' => 101],
            ['ID' => 202],
        ];

        ob_start();
        afficher_visuels_enigme(8);
        $html = ob_get_clean();

        $this->assertStringNotContainsString('galerie-enigme__thumbs', $html);
        $this->assertStringNotContainsString('galerie-enigme__nav', $html);
        $this->assertStringNotContainsString('Page 1 / 2', $html);
        $this->assertStringNotContainsString(' hidden', $html);
        $this->assertSame(2, substr_count($html, 'galerie-enigme__slide'));
        $this->assertStringContainsString('min(100vw, 1380px)', $html);
        $this->assertStringContainsString('galerie-enigme__slide is-active', $html);
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
        $this->assertStringNotContainsString(' hidden', $html);
        $this->assertStringContainsString('galerie-enigme__slide is-active', $html);
        $this->assertStringContainsString('galerie-enigme__slide--step', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/galerie-enigme__slide--step[^>]*\bis-active\b/',
            $html
        );
    }

    public function test_step_page_keeps_the_strip_width_and_a_free_height(): void
    {
        $edition = file_get_contents(
            dirname(__DIR__)
            . '/wp-content/themes/chassesautresor/template-parts/enigme/partials/enigme-partial-etapes-edition.php'
        );
        $css = file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/assets/scss/_enigme.scss'
        );

        $this->assertIsString($edition);
        $this->assertStringContainsString('Largeur 1380 px, hauteur libre', $edition);
        $this->assertStringContainsString('sans joint', $edition);
        $this->assertIsString($css);
        $this->assertMatchesRegularExpression(
            '/\.page-enigme\.page-enigme--reader \.galerie-enigme__slide \+ \.galerie-enigme__slide'
            . '[\s\S]{0,180}margin:\s*0/',
            $css
        );
        $this->assertStringContainsString(
            'body:has(.page-enigme--reader) .app-topbar',
            $css
        );
    }

    public function test_webtoon_strip_keeps_its_full_height_before_the_reply(): void
    {
        $css = file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/dist/style.css'
        );

        $this->assertIsString($css);
        $this->assertMatchesRegularExpression(
            '/\.page-enigme\.page-enigme--reader \.galerie-enigme__stage picture'
            . '[\s\S]{0,700}max-height:\s*none/',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/\.page-enigme\.page-enigme--reader \.galerie-enigme__stage \.enigme-media-zoom'
            . '[\s\S]{0,900}max-height:\s*none/',
            $css
        );
    }

    public function test_only_the_first_band_is_requested_before_the_script_runs(): void
    {
        $html = enigme_webtoon_band_html([
            ['url' => 'https://cdn.example/a-band0.jpg', 'width' => 1380, 'height' => 1000],
            ['url' => 'https://cdn.example/a-band1.jpg', 'width' => 1380, 'height' => 1000],
            ['url' => 'https://cdn.example/a-band2.jpg', 'width' => 1380, 'height' => 1000],
        ], 'Forêt', true);

        $this->assertSame(1, substr_count($html, ' src="'));
        $this->assertStringContainsString('src="https://cdn.example/a-band0.jpg"', $html);
        $this->assertStringContainsString('data-band-src="https://cdn.example/a-band1.jpg"', $html);
        $this->assertStringContainsString('data-band-src="https://cdn.example/a-band2.jpg"', $html);
        $this->assertStringNotContainsString(' src="https://cdn.example/a-band1.jpg"', $html);
        $this->assertStringNotContainsString(' src="https://cdn.example/a-band2.jpg"', $html);
        $this->assertStringContainsString('sizes="min(100vw, 1380px)"', $html);
    }
}
