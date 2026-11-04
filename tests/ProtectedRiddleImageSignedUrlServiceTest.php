<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\RiddleStepImageStorageService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageObscureNameService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageProtectionService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleStepImageStorageService.php';

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

    public function testStorageObscuresAFileBeforePublishingTheDirectory(): void
    {
        $source = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleStepImageStorageService.php'
        );

        self::assertLessThan(
            strpos($source, 'protection()->protect'),
            strpos($source, 'names()->obscureAttachment')
        );
        self::assertStringContainsString('class RiddleImageProtectionService', file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageProtectionService.php'
        ));
        unset($source);
    }
}
