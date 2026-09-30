<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionFileInputService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionFileInputService.php';

class SolutionFileInputServiceTest extends TestCase {
    public function testUploadedFileTakesPrecedenceOverPostedAttachment(): void {
        $result = (new SolutionFileInputService())->resolve(
            42,
            ['tmp_name' => '/tmp/upload'],
            true,
            12,
            static fn (int $solutionId): int => $solutionId + 57,
            static fn (): bool => false,
            static fn (): string => ''
        );

        $this->assertSame(['id' => 99, 'submitted' => true, 'error' => null], $result);
    }

    public function testPostedAttachmentAndExplicitRemovalAreNormalized(): void {
        $service = new SolutionFileInputService();
        $callbacks = [
            static fn (): int => 0,
            static fn (): bool => false,
            static fn (): string => '',
        ];

        $this->assertSame(
            ['id' => 12, 'submitted' => true, 'error' => null],
            $service->resolve(42, [], true, 12, ...$callbacks)
        );
        $this->assertSame(
            ['id' => 0, 'submitted' => true, 'error' => null],
            $service->resolve(42, [], true, 0, ...$callbacks)
        );
        $this->assertSame(
            ['id' => 0, 'submitted' => false, 'error' => null],
            $service->resolve(42, [], false, 0, ...$callbacks)
        );
    }

    public function testUploadErrorIsReturnedWithoutAttachmentId(): void {
        $error = (object) ['message' => 'upload failed'];
        $result = (new SolutionFileInputService())->resolve(
            42,
            ['tmp_name' => '/tmp/upload'],
            false,
            0,
            static fn () => $error,
            static fn ($value): bool => is_object($value),
            static fn ($value): string => $value->message
        );

        $this->assertSame(['id' => 0, 'submitted' => true, 'error' => 'upload failed'], $result);
    }
}
