<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\ProtectedRiddleImageDeliveryService;
use ChassesAuTresor\Core\Media\RiddleImageProtectionService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/ProtectedRiddleImageDeliveryService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageObscureNameService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageProtectionService.php';

final class ProtectedRiddleImageDeliveryServiceTest extends TestCase
{
    public function testProtectionRulesAllowDirectReadsWithoutListing(): void
    {
        $service = new RiddleImageProtectionService();
        $rules = $service->rules(42);

        self::assertStringContainsString('Options -Indexes', $rules);
        self::assertStringContainsString('IndexIgnore *', $rules);
        self::assertStringContainsString('REQUEST_FILENAME} -d', $rules);
        self::assertStringContainsString('[F,L]', $rules);
        self::assertStringContainsString("énigme 42", $rules);
        self::assertStringContainsString(RiddleImageProtectionService::PUBLIC_CACHE_CONTROL, $rules);
        self::assertStringContainsString('Cache-Control', $rules);
        self::assertStringNotContainsString('ORG_REQ_URI', $rules);
        self::assertStringNotContainsString('Require all denied', $rules);
        self::assertStringNotContainsString('HTTP_REFERER', $rules);
        self::assertStringNotContainsString('private', $rules);
        self::assertStringNotContainsString('voir-image-enigme', $rules);
    }

    public function testDirectoryStaysClosedWhileAGuessableNameRemains(): void
    {
        $source = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageProtectionService.php'
        );
        $check = strpos($source, 'riddleDirectoryIsUnpredictable');
        $write = strpos($source, 'file_put_contents($protected');

        self::assertNotFalse($check);
        self::assertNotFalse($write);
        self::assertLessThan($write, $check);
        self::assertStringContainsString('return false;', substr($source, $check, $write - $check));
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

    public function testRouteHandlerNoLongerServesRiddleImages(): void
    {
        $source = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/ProtectedAssetRouteHandler.php'
        );

        self::assertStringNotContainsString('voir-image-enigme', $source);
        self::assertStringNotContainsString('protected-riddle-image.php', $source);
        self::assertStringContainsString('protected-solution-file.php', $source);
        self::assertStringContainsString('voir-fichier', $source);
        self::assertFileDoesNotExist(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/protected-riddle-image.php'
        );
    }
}
