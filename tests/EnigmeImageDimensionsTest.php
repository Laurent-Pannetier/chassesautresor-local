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
if (!function_exists('wp_get_attachment_image_src')) {
    function wp_get_attachment_image_src($id, $size = 'full')
    {
        if (isset($GLOBALS['enigme_srcset_baseurl'], $GLOBALS['enigme_srcset_widths'])) {
            $width = $GLOBALS['enigme_srcset_widths'][$size] ?? 1920;

            return [
                $GLOBALS['enigme_srcset_baseurl'] . '/' . $size . '.jpg',
                $width,
                (int) round($width * 9 / 16),
            ];
        }
        $widths = [
            'thumbnail' => 150,
            'medium' => 300,
            'large' => 1024,
            'enigme-720' => 720,
            'enigme-1000' => 1000,
            'enigme-1380' => 1380,
            'full' => 1380,
        ];

        return [
            'https://example.com/uploads/' . $id . '-' . $size . '.jpg',
            $widths[$size] ?? 300,
            100,
        ];
    }
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
        $this->assertStringContainsString('123-full.jpg', $html);
        $this->assertStringContainsString('1380w', $html);
        $this->assertStringContainsString('123-large.jpg', $html);
        $this->assertStringContainsString('1024w', $html);
        $this->assertStringContainsString('123-medium.jpg', $html);
        $this->assertStringContainsString('300w', $html);
        $this->assertStringContainsString('123-thumbnail.jpg', $html);
        $this->assertStringContainsString('150w', $html);
        $this->assertStringNotContainsString('voir-image-enigme', $html);
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

        $this->assertStringContainsString('321-medium.jpg', $html);
        $this->assertStringContainsString('300w', $html);
        $this->assertStringContainsString('321-thumbnail.jpg', $html);
        $this->assertStringContainsString('150w', $html);
        $this->assertStringNotContainsString('media=', $html);
        $this->assertStringNotContainsString('<source', $html);
    }

    public function test_thumbnail_slot_does_not_request_a_larger_file(): void
    {
        $html = build_picture_enigme(42, 'Vignette', ['thumbnail'], ['sizes' => '4.25rem']);

        $this->assertStringContainsString('42-thumbnail.jpg', $html);
        $this->assertStringContainsString('150w', $html);
        $this->assertStringContainsString('sizes="4.25rem"', $html);
        $this->assertStringNotContainsString('42-medium.jpg', $html);
        $this->assertStringNotContainsString('42-large.jpg', $html);
        $this->assertStringNotContainsString(' 2x', $html);
    }

    public function test_hero_floor_skips_thumbnail_on_small_screens(): void
    {
        $html = build_picture_enigme(42, 'Planche', ['large', 'full'], ['sizes' => '100vw'], 'large');

        $this->assertStringNotContainsString('42-thumbnail.jpg', $html);
        $this->assertStringNotContainsString('42-medium.jpg', $html);
        $this->assertStringContainsString('42-large.jpg', $html);
        $this->assertStringContainsString('42-full.jpg', $html);
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

    public function test_strip_column_uses_width_capped_derivatives(): void
    {
        $this->assertSame(720, enigme_picture_size_width(0, 'enigme-720'));
        $this->assertSame(1000, enigme_picture_size_width(0, 'enigme-1000'));
        $this->assertSame(1380, enigme_picture_size_width(0, 'enigme-1380'));
        $this->assertSame(1380, enigme_picture_size_width(0, 'full'));
        $this->assertSame('min(100vw, 1380px)', enigme_hero_picture_sizes());
        $this->assertSame(
            ['enigme-720', 'enigme-1000', 'enigme-1380'],
            enigme_planche_size_names()
        );
    }

    public function test_hero_planche_keeps_intermediate_widths_when_their_webp_is_missing(): void
    {
        if (!function_exists('get_intermediate_image_sizes')) {
            function get_intermediate_image_sizes(): array
            {
                return ['thumbnail', 'medium', 'large', 'enigme-720', 'enigme-1000', 'enigme-1380'];
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
            'enigme-720' => 720,
            'enigme-1000' => 1000,
            'enigme-1380' => 1380,
            'full' => 1380,
        ];
        foreach (array_keys($widths) as $size) {
            file_put_contents($directory . '/' . $size . '.jpg', 'jpeg');
        }
        file_put_contents($directory . '/enigme-720.webp', 'webp');
        file_put_contents($directory . '/enigme-1380.webp', 'webp');

        if (!function_exists('wp_get_upload_dir')) {
            function wp_get_upload_dir(): array
            {
                return [
                    'baseurl' => $GLOBALS['enigme_srcset_baseurl'],
                    'basedir' => $GLOBALS['enigme_srcset_basedir'],
                ];
            }
        }
        $GLOBALS['enigme_srcset_baseurl'] = 'https://example.com/uploads';
        $GLOBALS['enigme_srcset_basedir'] = $directory;
        $GLOBALS['enigme_srcset_widths'] = $widths;

        try {
            $sizes = enigme_hero_picture_sizes();
            $html = build_picture_enigme(
                42,
                'Planche',
                ['enigme-720', 'full'],
                ['sizes' => $sizes],
                'enigme-720'
            );
        } finally {
            foreach (glob($directory . '/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($directory);
        }

        $this->assertSame('min(100vw, 1380px)', $sizes);
        $this->assertStringContainsString('720w', $html);
        $this->assertStringContainsString('1000w', $html);
        $this->assertStringContainsString('1380w', $html);
        $this->assertStringContainsString('enigme-720.jpg', $html);
        $this->assertStringContainsString('enigme-1000.jpg', $html);
        $this->assertStringNotContainsString('thumbnail.jpg', $html);
        $this->assertStringNotContainsString('medium.jpg', $html);
        $this->assertStringNotContainsString('large.jpg', $html);
        $this->assertStringNotContainsString('voir-image-enigme', $html);
        $this->assertStringContainsString('type="image/webp"', $html);
        $this->assertSame(2, substr_count($html, 'sizes="' . $sizes . '"'));
        preg_match('/<source\\b[^>]*>/', $html, $source);
        $this->assertNotEmpty($source);
        $this->assertStringContainsString('enigme-720.webp', $source[0]);
        $this->assertStringContainsString('enigme-1380.webp', $source[0]);
        $this->assertStringNotContainsString('fmt=webp', $source[0]);
        $this->assertStringNotContainsString('enigme-1000', $source[0]);
        $this->assertStringContainsString('sizes="' . $sizes . '"', $source[0]);
    }
}
