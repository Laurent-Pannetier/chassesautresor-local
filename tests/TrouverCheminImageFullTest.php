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

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/visuels.php';

        $result = trouver_chemin_image(1, 'full');

        $this->assertSame($filePath, $result['path']);
        $this->assertSame('full', $capturedSize);
    }
}
