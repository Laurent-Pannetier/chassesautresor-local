<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

class TrouverCheminImageFullTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_full_size_returns_existing_path(): void
    {
        global $uploadDir, $capturedSize;

        $uploadDir = sys_get_temp_dir() . '/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filePath = $uploadDir . '/image.jpg';
        file_put_contents($filePath, 'test');

        $capturedSize = null;
        $GLOBALS['protected_image_cache'] = [];

        ini_set('error_log', sys_get_temp_dir() . '/phpunit-error.log');

        if (!function_exists('wp_get_attachment_image_src')) {
            function wp_get_attachment_image_src($id, $size)
            {
                global $capturedSize;
                $capturedSize = $size;
                return ['http://example.com/uploads/image.jpg', 100, 100];
            }
        }

        if (!function_exists('wp_get_upload_dir')) {
            function wp_get_upload_dir()
            {
                global $uploadDir;
                return [
                    'baseurl' => 'http://example.com/uploads',
                    'basedir' => $uploadDir,
                ];
            }
        }

        if (!function_exists('wp_cache_get')) {
            function wp_cache_get($key, $group, $force = false, &$found = null)
            {
                $cacheKey = $group . ':' . $key;
                $found = array_key_exists($cacheKey, $GLOBALS['protected_image_cache']);
                return $found ? $GLOBALS['protected_image_cache'][$cacheKey] : false;
            }
        }

        if (!function_exists('wp_cache_set')) {
            function wp_cache_set($key, $value, $group): bool
            {
                $GLOBALS['protected_image_cache'][$group . ':' . $key] = $value;
                return true;
            }
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Media/ProtectedImagePathService.php';
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Media/protected-image-functions.php';

        $result = trouver_chemin_image(1, 'full');

        $this->assertSame($filePath, $result['path']);
        $this->assertSame('full', $capturedSize);
    }
}
