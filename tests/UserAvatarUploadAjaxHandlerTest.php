<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\UserAvatarUploadAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/UserAvatarUploadAjaxHandler.php';

final class UserAvatarUploadAjaxHandlerTest extends TestCase
{
    public function testKeepsPathsInsideTheUploadsDirectory(): void
    {
        $base = 'https://example.test/wp-content/uploads';

        self::assertSame(
            '/2026/10/photo.webp',
            UserAvatarUploadAjaxHandler::uploadRelativePath($base . '/2026/10/photo.webp', $base)
        );
        self::assertSame(
            '/2026/10/photo.webp',
            UserAvatarUploadAjaxHandler::uploadRelativePath($base . '/2026/10/photo.webp?v=1', $base . '/')
        );
    }

    public function testRejectsPathsOutsideTheUploadsDirectory(): void
    {
        $base = 'https://example.test/wp-content/uploads';

        self::assertNull(UserAvatarUploadAjaxHandler::uploadRelativePath($base . '/../../etc/passwd', $base));
        self::assertNull(UserAvatarUploadAjaxHandler::uploadRelativePath($base . '/2026/%2e%2e/passwd', $base));
        self::assertNull(UserAvatarUploadAjaxHandler::uploadRelativePath('https://evil.test/photo.webp', $base));
        self::assertNull(UserAvatarUploadAjaxHandler::uploadRelativePath($base . '/photo.webp', ''));
    }
}
