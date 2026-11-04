<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\RiddleStripUploadGuard;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageProtectionLifecycle.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleStripUploadGuard.php';

final class RiddleStripImagifyContextStub {
    protected int $resizing_threshold = 2560;

    public function threshold(): int {
        return $this->resizing_threshold;
    }
}

if (!function_exists('imagify_get_context')) {
    function imagify_get_context($context) {
        return $GLOBALS['test_imagify_context'] ?? null;
    }
}

final class RiddleStripUploadGuardTest extends TestCase {
    private string $directory = '';

    protected function setUp(): void {
        if (!function_exists('imagecreatetruecolor')) {
            $this->fail('GD is required to prove a 1380×4000 strip is stored intact.');
        }
        $this->directory = sys_get_temp_dir() . '/riddle-strip-' . uniqid('', true);
        mkdir($this->directory, 0775, true);
        $GLOBALS['test_imagify_context'] = new RiddleStripImagifyContextStub();
    }

    protected function tearDown(): void {
        unset($GLOBALS['test_imagify_context']);
        $this->removeTree($this->directory);
        parent::tearDown();
    }

    public function testRegistersTheThresholdAfterImagify(): void {
        $filters = [];
        RiddleStripUploadGuard::register(
            static function ($hook, $callback, $priority, $acceptedArgs) use (&$filters): void {
            },
            static function ($hook, $callback, $priority, $acceptedArgs) use (&$filters): void {
                $filters[] = [$hook, $priority, $acceptedArgs];
            }
        );

        $threshold = $this->hook($filters, 'big_image_size_threshold');
        $this->assertSame(PHP_INT_MAX, $threshold[1]);
        $this->assertGreaterThan(PHP_INT_MAX - 30, $threshold[1]);
        $this->assertSame(4, $threshold[2]);
        $this->assertSame(PHP_INT_MAX, $this->hook($filters, 'update_attached_file')[1]);
        $this->assertNotNull($this->hook($filters, 'pre_get_imagify_option_resize_larger'));
        $this->assertNotNull($this->hook($filters, 'acf/upload_prefilter/name=etape_image'));
        $this->assertNotNull($this->hook($filters, 'wp_handle_upload_prefilter'));
    }

    public function testStoredOriginalStays1380By4000(): void {
        $source = $this->directory . '/strip.jpg';
        $this->writeJpeg($source, RiddleStripUploadGuard::STRIP_WIDTH, RiddleStripUploadGuard::STRIP_HEIGHT);

        $unguarded = new RiddleStripUploadGuard();
        $this->assertTrue($unguarded->shouldScale($source, 2560));
        $scaled = $this->directory . '/strip-scaled-by-wordpress.jpg';
        $this->fitInside($source, $scaled, 2560);
        $scaledSize = getimagesize($scaled);
        $this->assertIsArray($scaledSize);
        $this->assertSame(883, $scaledSize[0]);
        $this->assertSame(2560, $scaledSize[1]);

        $guard = new RiddleStripUploadGuard();
        $guard->beginStripUpload();
        $this->assertFalse($guard->threshold(2560, [1380, 4000, 'image/jpeg'], $source, 0));
        $this->assertFalse($guard->shouldScale($source, 2560));
        $this->assertSame(0, $guard->skipImagifyResize(null, 1));
        $this->assertSame(0, $GLOBALS['test_imagify_context']->threshold());

        $stored = $guard->preserveFile($source, $this->directory . '/_enigmes/enigme-4/strip.jpg');
        $storedSize = getimagesize($stored);
        $this->assertIsArray($storedSize);
        $this->assertSame(1380, $storedSize[0]);
        $this->assertSame(4000, $storedSize[1]);
        $this->assertNotSame($scaledSize, $storedSize);
    }

    public function testScaledReplacementKeepsTheUnscaledSibling(): void {
        $full = $this->directory . '/bande.jpg';
        $scaled = $this->directory . '/bande-scaled.jpg';
        $this->writeJpeg($full, 1380, 4000);
        $this->writeJpeg($scaled, 883, 2560);

        $guard = new RiddleStripUploadGuard();
        $guard->beginStripUpload();
        $kept = $guard->keepFullFile($scaled, 0);

        $this->assertSame($full, $kept);
        $this->assertFileDoesNotExist($scaled);
        $size = getimagesize($kept);
        $this->assertIsArray($size);
        $this->assertSame(1380, $size[0]);
        $this->assertSame(4000, $size[1]);
    }

    public function testOnlyRiddlePathsSkipTheLongSideLimit(): void {
        $guard = new RiddleStripUploadGuard();
        $strip = '/var/www/uploads/_enigmes/enigme-9/bande.jpg';
        $photo = '/var/www/uploads/photo.jpg';

        $this->assertFalse($guard->threshold(2560, [1380, 4000], $strip, 0));
        $this->assertFalse($guard->threshold(2560, [1380, 1800], $strip, 0));
        $this->assertFalse($guard->threshold(2560, [1380, 5200], $strip, 0));
        $this->assertSame(2560, $guard->threshold(2560, [4000, 3000], $photo, 0));
        $this->assertNull($guard->skipImagifyResize(null, 1));
    }

    /**
     * @param array<int, array{0:string,1:int,2:int}> $hooks
     * @return array{0:string,1:int,2:int}|null
     */
    private function hook(array $hooks, string $name): ?array {
        foreach ($hooks as $hook) {
            if ($hook[0] === $name) {
                return $hook;
            }
        }

        return null;
    }

    private function removeTree(string $path): void {
        if (!is_dir($path)) {
            return;
        }
        $items = scandir($path);
        if (!is_array($items)) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $child = $path . '/' . $item;
            if (is_dir($child)) {
                $this->removeTree($child);
                continue;
            }
            unlink($child);
        }
        rmdir($path);
    }

    private function writeJpeg(string $path, int $width, int $height): void {
        $image = imagecreatetruecolor($width, $height);
        $this->assertNotFalse($image);
        $ink = imagecolorallocate($image, 20, 40, 80);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $ink);
        imagejpeg($image, $path, 30);
        imagedestroy($image);
    }

    private function fitInside(string $source, string $destination, int $threshold): void {
        $size = getimagesize($source);
        $this->assertIsArray($size);
        $width = (int) $size[0];
        $height = (int) $size[1];
        $ratio = min($threshold / $width, $threshold / $height);
        $targetWidth = (int) round($width * $ratio);
        $targetHeight = (int) round($height * $ratio);
        $from = imagecreatefromjpeg($source);
        $to = imagecreatetruecolor($targetWidth, $targetHeight);
        $this->assertNotFalse($from);
        $this->assertNotFalse($to);
        imagecopyresampled($to, $from, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagejpeg($to, $destination, 30);
        imagedestroy($from);
        imagedestroy($to);
    }
}
