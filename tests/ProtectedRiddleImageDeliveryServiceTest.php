<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\ProtectedRiddleImageDeliveryService;
use ChassesAuTresor\Core\Media\RiddleImageProtectionService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/ProtectedRiddleImageDeliveryService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageProtectionService.php';

final class ProtectedRiddleImageDeliveryServiceTest extends TestCase
{
    public function testDetectsLiteSpeedServerSoftware(): void
    {
        $service = new ProtectedRiddleImageDeliveryService();

        self::assertTrue($service->isLiteSpeedServer('LiteSpeed'));
        self::assertTrue($service->isLiteSpeedServer('LiteSpeed/6.0'));
        self::assertFalse($service->isLiteSpeedServer('Apache/2.4'));
        self::assertFalse($service->isLiteSpeedServer('nginx'));
    }

    public function testBuildsUriUnderUploadsBase(): void
    {
        $service = new ProtectedRiddleImageDeliveryService();

        self::assertSame(
            '/wp-content/uploads/_enigmes/enigme-12/photo.webp',
            $service->absolutePathToUri(
                '/var/www/html/wp-content/uploads/_enigmes/enigme-12/photo.webp',
                '/var/www/html/wp-content/uploads',
                'https://example.test/wp-content/uploads'
            )
        );
    }

    public function testRejectsPathOutsideUploadsBase(): void
    {
        $service = new ProtectedRiddleImageDeliveryService();

        self::assertNull(
            $service->absolutePathToUri(
                '/etc/passwd',
                '/var/www/html/wp-content/uploads',
                'https://example.test/wp-content/uploads'
            )
        );
    }

    public function testProtectionRulesAllowLiteSpeedInternalRedirectPattern(): void
    {
        $service = new RiddleImageProtectionService();
        $rules = $service->rules(42);

        self::assertStringContainsString('ORG_REQ_URI', $rules);
        self::assertStringContainsString('/_enigmes/', $rules);
        self::assertStringContainsString('[F,L]', $rules);
        self::assertStringNotContainsString('Require all denied', $rules);
        self::assertStringContainsString("énigme 42", $rules);
        self::assertStringNotContainsString('HTTP_REFERER', $rules);
        self::assertStringNotContainsString('wp-admin', $rules);
        self::assertStringContainsString('RewriteRule ^ - [F,L]', $rules);
    }

    public function testConfinesReadableFilesToTheUploadsDirectory(): void
    {
        $root = sys_get_temp_dir() . '/cta-uploads-' . uniqid('', true);
        mkdir($root . '/_enigmes', 0777, true);
        $inside = $root . '/_enigmes/photo.jpg';
        file_put_contents($inside, 'img');
        $outside = sys_get_temp_dir() . '/cta-secret-' . uniqid('', true);
        file_put_contents($outside, 'secret');

        $service = new ProtectedRiddleImageDeliveryService();
        $confined = $service->confineToUploads($inside, $root);

        self::assertSame(str_replace('\\', '/', (string) realpath($inside)), $confined);
        self::assertNull($service->confineToUploads($outside, $root));
        self::assertNull($service->confineToUploads($root . '/_enigmes/missing.jpg', $root));

        @unlink($inside);
        @unlink($outside);
        @rmdir($root . '/_enigmes');
        @rmdir($root);
    }

    public function testControllerHardensFormatPathAndResponseHeaders(): void
    {
        $source = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/protected-riddle-image.php'
        );

        self::assertStringContainsString('confineToUploads', $source);
        self::assertStringContainsString("'fmt'", $source);
        self::assertStringContainsString('X-Content-Type-Options: nosniff', $source);
        self::assertStringContainsString('Referrer-Policy: no-referrer', $source);
        self::assertStringContainsString('image/webp', $source);
        self::assertLessThan(
            strpos($source, 'readfile($path)'),
            strpos($source, 'confineToUploads')
        );
    }

    public function testProtectedControllerPrefersLiteSpeedLocationBeforeReadfile(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/protected-riddle-image.php'
        );

        self::assertStringContainsString('ProtectedRiddleImageDeliveryService', $source);
        self::assertStringContainsString('tryLiteSpeedSend', $source);
        self::assertStringContainsString('X-LiteSpeed-Location', file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/ProtectedRiddleImageDeliveryService.php'
        ));
        self::assertLessThan(
            strpos($source, 'readfile($path)'),
            strpos($source, 'tryLiteSpeedSend')
        );
    }

    public function testOriginalBytesAreKeptUnlessWebpWasRequested(): void
    {
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Media/ProtectedImagePathService.php';

        $directory = sys_get_temp_dir() . '/cta-fmt-' . uniqid('', true);
        mkdir($directory, 0777, true);
        $jpeg = $directory . '/photo.jpg';
        $webp = $directory . '/photo.webp';
        file_put_contents($jpeg, 'jpg');
        file_put_contents($webp, 'webp');

        $service = new \ChassesAuTresor\Core\Media\ProtectedImagePathService();
        $original = $service->applyFormat($jpeg, '');
        $requested = $service->applyFormat($jpeg, 'webp');

        self::assertSame('image/jpeg', $original['mime']);
        self::assertSame($jpeg, $original['path']);
        self::assertSame('image/webp', $requested['mime']);
        self::assertSame($webp, $requested['path']);
        self::assertSame('image/jpeg', $service->applyFormat($jpeg, 'avif')['mime']);

        @unlink($jpeg);
        @unlink($webp);
        @rmdir($directory);
    }
}
