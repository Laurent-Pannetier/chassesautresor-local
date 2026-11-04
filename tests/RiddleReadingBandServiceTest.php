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
        $this->assertSame([1000, 1000, 500], array_map(static fn (array $band): int => $band['height'], $bands));
        $this->assertSame(20, $bands[0]['width']);
        $this->assertNotSame($source, $bands[0]['path']);
        $info = getimagesize($bands[2]['path']);
        $this->assertSame(20, $info[0] ?? 0);
        $this->assertSame(500, $info[1] ?? 0);

        $hashes = array_map('md5_file', array_column($bands, 'path'));
        $again = $service->ensure($source);
        $this->assertSame(array_column($bands, 'path'), array_column($again, 'path'));
        $this->assertSame($hashes, array_map('md5_file', array_column($again, 'path')));
    }

    public function testShortImageStaysTheOriginalFile(): void
    {
        $source = $this->jpeg(20, 400);
        $bands = (new RiddleReadingBandService())->ensure($source);

        $this->assertSame([['path' => $source, 'width' => 20, 'height' => 400]], $bands);
        $this->assertSame([$source], glob($this->directory . '/*') ?: []);
    }

    private function jpeg(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        $path = $this->directory . '/planche.jpg';
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
