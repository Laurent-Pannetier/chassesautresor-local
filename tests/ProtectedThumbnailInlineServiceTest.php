<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\ProtectedRiddleImageDeliveryService;
use ChassesAuTresor\Core\Media\ProtectedThumbnailInlineService;
use PHPUnit\Framework\TestCase;

final class ProtectedThumbnailInlineServiceTest extends TestCase
{
    private string $root = '';

    /** @var list<string> */
    private array $cleanup = [];

    protected function setUp(): void
    {
        if (!function_exists('esc_attr')) {
            eval('function esc_attr($text): string {'
                . ' return htmlspecialchars((string) $text, ENT_QUOTES, "UTF-8"); }');
        }
        if (!function_exists('wp_get_upload_dir')) {
            eval('function wp_get_upload_dir(): array {'
                . ' return ["basedir" => (string) ($GLOBALS["cta_uploads_basedir"] ?? "")]; }');
        }

        $this->root = sys_get_temp_dir() . '/cta-thumb-' . bin2hex(random_bytes(4));
        mkdir($this->root, 0777, true);
        $this->cleanup[] = $this->root;
        $GLOBALS['cta_uploads_basedir'] = $this->root;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['cta_uploads_basedir']);
        foreach ($this->cleanup as $dir) {
            $this->removeTree($dir);
        }
    }

    public function testSmallThumbnailIsInlinedWithoutAReusableUrl(): void
    {
        $path = $this->writePng($this->root . '/thumb.png');
        $html = $this->service($this->fixed($path))->html(501, 'Carte');

        $encoded = base64_encode((string) file_get_contents($path));
        self::assertStringContainsString('src="data:image/png;base64,' . $encoded . '"', $html);
        self::assertStringContainsString('alt="Carte"', $html);
        self::assertStringContainsString('width="1"', $html);
        self::assertStringContainsString('height="1"', $html);
        self::assertStringContainsString('loading="eager"', $html);
        self::assertStringContainsString('decoding="sync"', $html);
        self::assertStringNotContainsString('voir-image-enigme', $html);
        self::assertStringNotContainsString('loading="lazy"', $html);
    }

    public function testWebpCandidateWinsWhenItFits(): void
    {
        $webp = $this->writePng($this->root . '/thumb.webp');
        $original = $this->writePng($this->root . '/thumb.png', true);
        $html = $this->service(static function (int $id, string $size, string $format) use ($webp, $original): ?array {
            self::assertSame(11, $id);
            self::assertSame('thumbnail', $size);
            $path = $format === 'webp' ? $webp : $original;

            return ['path' => $path, 'mime' => 'image/png'];
        })->html(11);

        self::assertStringContainsString(base64_encode((string) file_get_contents($webp)), $html);
        self::assertStringNotContainsString(base64_encode((string) file_get_contents($original)), $html);
    }

    public function testOversizedWebpFallsBackToTheSmallerOriginal(): void
    {
        $webp = $this->writePng($this->root . '/big.webp');
        file_put_contents($webp, (string) file_get_contents($webp) . str_repeat('A', 80));
        $original = $this->writePng($this->root . '/small.png');
        $service = new ProtectedThumbnailInlineService(
            static function (int $id, string $size, string $format) use ($webp, $original): ?array {
                return ['path' => $format === 'webp' ? $webp : $original, 'mime' => 'image/png'];
            },
            new ProtectedRiddleImageDeliveryService(),
            100
        );

        $html = $service->html(11);

        self::assertStringContainsString(base64_encode((string) file_get_contents($original)), $html);
        self::assertStringNotContainsString('voir-image-enigme', $html);
    }

    public function testOversizedFileStaysOutOfThePage(): void
    {
        $path = $this->writePng($this->root . '/fat.png');
        file_put_contents($path, (string) file_get_contents($path) . str_repeat('B', 80));
        $service = new ProtectedThumbnailInlineService(
            $this->fixed($path),
            new ProtectedRiddleImageDeliveryService(),
            40
        );

        self::assertSame('', $service->html(11));
    }

    public function testPathOutsideUploadsIsRefused(): void
    {
        $outside = sys_get_temp_dir() . '/cta-thumb-out-' . bin2hex(random_bytes(4));
        mkdir($outside, 0777, true);
        $this->cleanup[] = $outside;
        $path = $this->writePng($outside . '/secret.png');

        self::assertSame('', $this->service($this->fixed($path))->html(11));
    }

    public function testNonImageBytesAreRefused(): void
    {
        $path = $this->root . '/notes.txt';
        file_put_contents($path, 'not an image');

        self::assertSame('', $this->service($this->fixed($path))->html(11));
    }

    public function testMissingFileIsRefused(): void
    {
        self::assertSame('', $this->service($this->fixed($this->root . '/missing.png'))->html(11));
    }

    public function testZeroImageIdDoesNotReadAFile(): void
    {
        $service = $this->service(static function (): ?array {
            self::fail('A missing image must not be resolved.');
        });

        self::assertSame('', $service->html(0));
    }

    public function testSignedImageProxyStillChecksAccess(): void
    {
        $source = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/protected-riddle-image.php'
        );

        self::assertStringContainsString('$signature_policy->acceptsSignature(', $source);
        self::assertStringContainsString('$asset_service->canViewRiddle(', $source);
        self::assertStringContainsString('$step_image_service->canView(', $source);
    }

    /** @param callable(int,string,string):(?array{path:string,mime:string}) $locate */
    private function service($locate): ProtectedThumbnailInlineService
    {
        return new ProtectedThumbnailInlineService($locate, new ProtectedRiddleImageDeliveryService());
    }

    /** @return callable(int,string,string):(?array{path:string,mime:string}) */
    private function fixed(string $path): callable
    {
        return static function (int $id, string $size, string $format) use ($path): ?array {
            return ['path' => $path, 'mime' => 'image/png'];
        };
    }

    private function writePng(string $path, bool $variant = false): string
    {
        $pixel = $variant
            ? 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
            : 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
        file_put_contents($path, base64_decode($pixel, true));

        return $path;
    }

    private function removeTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeTree($path);
                continue;
            }
            unlink($path);
        }
        rmdir($dir);
    }
}
