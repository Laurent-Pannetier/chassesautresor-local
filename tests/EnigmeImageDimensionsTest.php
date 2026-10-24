<?php
use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!function_exists('esc_url')) {
    function esc_url($url) { return $url; }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) { return $text; }
}
if (!function_exists('site_url')) {
    function site_url($path = '') { return 'https://example.com' . $path; }
}
if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
}
if (!function_exists('get_field')) {
    function get_field($key, $id, $format = true) { return [$id]; }
}
if (!function_exists('get_stylesheet_directory')) {
    function get_stylesheet_directory() { return __DIR__; }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/visuels.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class EnigmeImageDimensionsTest extends TestCase
{
    public function test_picture_contains_breakpoint_sources(): void
    {
        ob_start();
        afficher_picture_vignette_enigme(123, 'Alt', ['full']);
        $html = ob_get_clean();

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('taille=full', $html);
        $this->assertStringContainsString('1920w', $html);
        $this->assertStringContainsString('taille=large', $html);
        $this->assertStringContainsString('1024w', $html);
        $this->assertStringContainsString('taille=medium', $html);
        $this->assertStringContainsString('300w', $html);
        $this->assertStringContainsString('taille=thumbnail', $html);
        $this->assertStringContainsString('150w', $html);
        $this->assertStringNotContainsString('media=', $html);
        $this->assertStringNotContainsString(' 1x', $html);
        $this->assertStringNotContainsString(' 2x', $html);
        $this->assertGreaterThan(0, substr_count($html, 'srcset='));
    }

    public function test_medium_size_includes_thumbnail_fallback(): void
    {
        ob_start();
        afficher_picture_vignette_enigme(321, 'Alt', ['medium']);
        $html = ob_get_clean();

        $this->assertStringContainsString('taille=medium', $html);
        $this->assertStringContainsString('300w', $html);
        $this->assertStringContainsString('taille=thumbnail', $html);
        $this->assertStringContainsString('150w', $html);
        $this->assertStringNotContainsString('media=', $html);
        $this->assertStringNotContainsString('<source', $html);
    }

    public function test_thumbnail_slot_does_not_request_a_larger_file(): void
    {
        $html = build_picture_enigme(42, 'Vignette', ['thumbnail'], ['sizes' => '4.25rem']);

        $this->assertStringContainsString('taille=thumbnail', $html);
        $this->assertStringContainsString('150w', $html);
        $this->assertStringContainsString('sizes="4.25rem"', $html);
        $this->assertStringNotContainsString('taille=medium', $html);
        $this->assertStringNotContainsString('taille=large', $html);
        $this->assertStringNotContainsString(' 2x', $html);
    }

    public function test_hero_floor_skips_thumbnail_on_small_screens(): void
    {
        $html = build_picture_enigme(42, 'Planche', ['large', 'full'], ['sizes' => '100vw'], 'large');

        $this->assertStringNotContainsString('taille=thumbnail', $html);
        $this->assertStringNotContainsString('taille=medium', $html);
        $this->assertStringContainsString('taille=large', $html);
        $this->assertStringContainsString('taille=full', $html);
        $this->assertStringContainsString('sizes="100vw"', $html);
    }

    public function test_webp_srcset_is_omitted_when_the_largest_file_has_no_webp(): void
    {
        $srcset = enigme_picture_webp_srcset([
            ['url' => 'large.jpg', 'webp' => 'large.webp', 'width' => 1024],
            ['url' => 'full.jpg', 'webp' => '', 'width' => 1920],
        ]);

        $this->assertSame('', $srcset);
    }

    public function test_hero_planche_keeps_intermediate_widths_when_their_webp_is_missing(): void
    {
        if (!function_exists('get_intermediate_image_sizes')) {
            function get_intermediate_image_sizes(): array
            {
                return ['thumbnail', 'medium', 'large', 'enigme-1280', 'enigme-1600'];
            }
        }
        if (!function_exists('wp_image_editor_supports')) {
            function wp_image_editor_supports($args = []): bool
            {
                return true;
            }
        }

        $directory = sys_get_temp_dir() . '/enigme-srcset-' . uniqid('', true);
        mkdir($directory);
        $widths = [
            'large' => 1024,
            'enigme-1280' => 1280,
            'enigme-1600' => 1600,
            'full' => 1920,
        ];
        foreach (array_keys($widths) as $size) {
            file_put_contents($directory . '/' . $size . '.jpg', 'jpeg');
        }
        file_put_contents($directory . '/large.webp', 'webp');
        file_put_contents($directory . '/full.webp', 'webp');

        if (!function_exists('wp_get_upload_dir')) {
            function wp_get_upload_dir(): array
            {
                return [
                    'baseurl' => $GLOBALS['enigme_srcset_baseurl'],
                    'basedir' => $GLOBALS['enigme_srcset_basedir'],
                ];
            }
        }
        if (!function_exists('wp_get_attachment_image_src')) {
            function wp_get_attachment_image_src($id, $size): array
            {
                $width = $GLOBALS['enigme_srcset_widths'][$size] ?? 1920;
                $url = $GLOBALS['enigme_srcset_baseurl'] . '/' . $size . '.jpg';

                return [$url, $width, (int) round($width * 9 / 16)];
            }
        }

        $GLOBALS['enigme_srcset_baseurl'] = 'https://example.com/uploads';
        $GLOBALS['enigme_srcset_basedir'] = $directory;
        $GLOBALS['enigme_srcset_widths'] = $widths;

        try {
            $sizes = enigme_hero_picture_sizes();
            $html = build_picture_enigme(42, 'Planche', ['large', 'full'], ['sizes' => $sizes], 'large');
        } finally {
            foreach (glob($directory . '/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($directory);
        }

        $this->assertStringContainsString('1024w', $html);
        $this->assertStringContainsString('1280w', $html);
        $this->assertStringContainsString('1600w', $html);
        $this->assertStringContainsString('1920w', $html);
        $this->assertStringContainsString('taille=enigme-1280', $html);
        $this->assertStringContainsString('taille=enigme-1600', $html);
        $this->assertStringNotContainsString('taille=thumbnail', $html);
        $this->assertStringNotContainsString('taille=medium', $html);
        $this->assertStringContainsString('type="image/webp"', $html);
        $this->assertSame(2, substr_count($html, 'sizes="' . $sizes . '"'));
        preg_match('/<source\\b[^>]*>/', $html, $source);
        $this->assertNotEmpty($source);
        $this->assertStringContainsString('fmt=webp', $source[0]);
        $this->assertStringContainsString('taille=large', $source[0]);
        $this->assertStringContainsString('taille=full', $source[0]);
        $this->assertStringNotContainsString('enigme-1280', $source[0]);
        $this->assertStringNotContainsString('enigme-1600', $source[0]);
        $this->assertStringContainsString('sizes="' . $sizes . '"', $source[0]);
    }
}
