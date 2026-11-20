<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\RiddleReadingBandService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleReadingBandService.php';

final class RiddleReadingBandServiceTest extends TestCase
{
    private string $directory = '';

    protected function setUp(): void
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagejpeg')) {
            $this->fail('GD is required to cut a strip into static bands.');
        }
        $this->directory = sys_get_temp_dir() . '/riddle-bands-' . uniqid('', true);
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        if ($this->directory !== '' && is_dir($this->directory)) {
            $this->removeTree($this->directory);
        }
    }

    public function testTallStripIsCutIntoStaticBandsAndLeftAloneTheSecondTime(): void
    {
        $source = $this->jpeg(20, 2500);
        $service = new RiddleReadingBandService();
        $bands = $service->ensure($source);

        $this->assertCount(3, $bands);
        $this->assertSame([1008, 1008, 486], array_map(static fn (array $band): int => $band['height'], $bands));
        $this->assertSame(20, $bands[0]['width']);
        $this->assertNotSame($source, $bands[0]['path']);
        $this->assertSame([20], array_column($bands[0]['sources'], 'width'));
        $info = getimagesize($bands[2]['path']);
        $this->assertSame(20, $info[0] ?? 0);
        $this->assertSame(486, $info[1] ?? 0);

        $paths = [];
        foreach ($bands as $band) {
            foreach ($band['sources'] as $piece) {
                $paths[] = $piece['path'];
            }
        }
        $hashes = array_map('md5_file', $paths);
        $again = $service->ensure($source);
        $this->assertSame(array_column($bands, 'path'), array_column($again, 'path'));
        $againPaths = [];
        foreach ($again as $band) {
            foreach ($band['sources'] as $piece) {
                $againPaths[] = $piece['path'];
            }
        }
        $this->assertSame($paths, $againPaths);
        $this->assertSame($hashes, array_map('md5_file', $againPaths));
    }

    public function testWideStripWrites720And1000VariantsOnBothEngines(): void
    {
        foreach (['imagick', 'gd'] as $engine) {
            $source = $this->jpeg(1100, 1100, 'wide-' . $engine . '.jpg');
            $bands = (new RiddleReadingBandService($engine))->ensure($source);

            $this->assertCount(2, $bands, $engine);
            $this->assertSame([1008, 93], array_column($bands, 'height'), $engine);
            $this->assertSame([720, 1000, 1100], array_column($bands[0]['sources'], 'width'), $engine);
            foreach ($bands as $band) {
                foreach ($band['sources'] as $piece) {
                    $info = getimagesize($piece['path']);
                    $this->assertSame($piece['width'], $info[0] ?? 0, $engine . ' ' . $piece['path']);
                    $this->assertSame($piece['height'], $info[1] ?? 0, $engine . ' ' . $piece['path']);
                }
            }
            $extension = pathinfo($bands[0]['path'], PATHINFO_EXTENSION);
            $this->assertSame('webp', $extension, $engine);
        }
    }

    public function testStaleJpegBandIsRemovedOnceWebpBandsExist(): void
    {
        $source = $this->jpeg(20, 1200);
        $service = new RiddleReadingBandService();
        $service->ensure($source);
        $stale = $this->directory . '/planche-band9.jpg';
        file_put_contents($stale, 'old');

        $service->ensure($source);

        $this->assertFileDoesNotExist($stale);
    }

    public function testSubsizesTallerThanOneBandAreDroppedForStripsOnly(): void
    {
        $sizes = [
            'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => 1],
            'medium' => ['width' => 300, 'height' => 300, 'crop' => 0],
            'large' => ['width' => 1024, 'height' => 1024, 'crop' => 0],
            'enigme-720' => ['width' => 720, 'height' => 0, 'crop' => 0],
            'medium_large' => ['width' => 768, 'height' => 0, 'crop' => 0],
        ];
        $service = new RiddleReadingBandService();
        $strip = '/var/uploads/_enigmes/enigme-1/a.webp';

        $kept = $service->filterSubsizes($sizes, 1380, 13000, $strip);
        $this->assertSame(['thumbnail', 'medium'], array_keys($kept));
        $this->assertSame(
            ['thumbnail', 'medium'],
            array_keys($service->filterSubsizes($sizes, 1380, 13000, '_enigmes/enigme-1/a.webp'))
        );
        $this->assertSame($sizes, $service->filterSubsizes($sizes, 1380, 13000, '/var/uploads/2026/a.webp'));
        $this->assertSame($sizes, $service->filterSubsizes($sizes, 1380, 800, $strip));
    }

    public function testBandFileNamesAreNotCutAgain(): void
    {
        $service = new RiddleReadingBandService();

        $this->assertTrue($service->isBandFile('/tmp/planche-band0.webp'));
        $this->assertTrue($service->isBandFile('/tmp/planche-band2-w720.webp'));
        $this->assertFalse($service->isBandFile('/tmp/planche.webp'));
        $this->assertTrue(RiddleReadingBandService::splits(13000));
        $this->assertFalse(RiddleReadingBandService::splits(1008));
    }

    public function testHeadLimitWritesOnlyTheFirstMissingBands(): void
    {
        $source = $this->jpeg(20, 2500);
        $service = new RiddleReadingBandService();
        $stale = $this->directory . '/planche-band9.jpg';
        file_put_contents($stale, 'old');

        $described = $service->ensure($source, 0);
        $this->assertCount(3, $described);
        $this->assertFalse($described[0]['ready']);
        $this->assertFileDoesNotExist($described[0]['path']);
        $this->assertFileExists($stale);

        $head = $service->ensure($source, 1);
        $this->assertTrue($head[0]['ready']);
        $this->assertFalse($head[1]['ready']);
        $this->assertFalse($head[2]['ready']);
        $this->assertFileExists($head[0]['path']);
        $this->assertFileDoesNotExist($head[1]['path']);
        $this->assertFileExists($stale);
        $headHash = md5_file($head[0]['path']);

        $done = $service->ensure($source);
        $this->assertTrue($done[0]['ready']);
        $this->assertTrue($done[1]['ready']);
        $this->assertTrue($done[2]['ready']);
        $this->assertSame($headHash, md5_file($done[0]['path']));
        $this->assertFileExists($done[2]['path']);
        $this->assertFileDoesNotExist($stale);

        $again = $service->ensure($source, 1);
        $this->assertTrue($again[2]['ready']);
        $this->assertFileExists($again[2]['path']);
    }

    public function testShortImageStaysTheOriginalFile(): void
    {
        $source = $this->jpeg(20, 400);
        $bands = (new RiddleReadingBandService())->ensure($source);

        $this->assertSame(
            [['path' => $source, 'width' => 20, 'height' => 400, 'ready' => true]],
            $bands
        );
        $this->assertSame([$source], glob($this->directory . '/*') ?: []);
    }

    private function jpeg(int $width, int $height, string $name = 'planche.jpg'): string
    {
        $image = imagecreatetruecolor($width, $height);
        $path = $this->directory . '/' . $name;
        imagejpeg($image, $path, 85);
        imagedestroy($image);

        return $path;
    }

    private function removeTree(string $directory): void
    {
        $entries = scandir($directory);
        if ($entries === false) {
            return;
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $directory . '/' . $entry;
            if (is_dir($path)) {
                $this->removeTree($path);
                continue;
            }
            unlink($path);
        }
        rmdir($directory);
    }
}
