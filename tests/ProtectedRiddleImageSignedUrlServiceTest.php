<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\ProtectedRiddleImageSignedUrlService;
use ChassesAuTresor\Core\Media\RiddleStepImageStorageService;
use PHPUnit\Framework\TestCase;

if (!function_exists('wp_salt')) {
    function wp_salt(string $scheme = 'auth'): string
    {
        return 'test-image-signing-secret';
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url = '')
    {
        $query = http_build_query($args);
        return $url . (str_contains((string) $url, '?') ? '&' : '?') . $query;
    }
}

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/ProtectedRiddleImageSignedUrlService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageProtectionService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleStepImageStorageService.php';

final class ProtectedRiddleImageSignedUrlServiceTest extends TestCase
{
    public function testSignAndVerifyRoundTrip(): void
    {
        $service = new ProtectedRiddleImageSignedUrlService(600);
        $now = 1_700_000_000;
        $signed = $service->sign(42, 'medium', 7, $now);

        self::assertSame($now + 600, $signed['exp']);
        self::assertSame(7, $signed['uid']);
        self::assertTrue(
            $service->verify(42, 'medium', 7, $signed['exp'], $signed['sig'], $now + 10)
        );
        self::assertFalse(
            $service->verify(42, 'medium', 7, $signed['exp'], $signed['sig'], $now + 601)
        );
        self::assertFalse(
            $service->verify(42, 'medium', 8, $signed['exp'], $signed['sig'], $now + 10)
        );
        self::assertFalse(
            $service->verify(43, 'medium', 7, $signed['exp'], $signed['sig'], $now + 10)
        );
    }

    public function testBuildUrlContainsSignatureParams(): void
    {
        $service = new ProtectedRiddleImageSignedUrlService(120);
        $url = $service->buildUrl(9, 'large', 3, 'https://example.test/voir-image-enigme', 1_700_000_000);

        self::assertStringContainsString('id=9', $url);
        self::assertStringContainsString('taille=large', $url);
        self::assertStringContainsString('uid=3', $url);
        self::assertStringContainsString('exp=', $url);
        self::assertStringContainsString('sig=', $url);
    }
}

final class RiddleStepImageStoragePathTest extends TestCase
{
    public function testDetectsProtectedRelativePaths(): void
    {
        $service = new RiddleStepImageStorageService();

        self::assertTrue($service->isProtectedPath('_enigmes/enigme-15/etapes/photo.jpg', 15));
        self::assertTrue($service->isProtectedPath('_enigmes/enigme-15/visuel.jpg', 15));
        self::assertFalse($service->isProtectedPath('2024/01/photo.jpg', 15));
        self::assertFalse($service->isProtectedPath('_enigmes/enigme-99/etapes/photo.jpg', 15));
    }

    public function testTargetDirectoryUsesEtapesSubfolder(): void
    {
        $service = new RiddleStepImageStorageService();

        self::assertSame(
            '/var/www/uploads/_enigmes/enigme-4/etapes',
            $service->targetDirectory(4, '/var/www/uploads')
        );
    }
}
