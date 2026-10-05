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
}
