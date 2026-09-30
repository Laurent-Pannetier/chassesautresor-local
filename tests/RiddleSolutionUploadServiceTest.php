<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleSolutionUploadService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleSolutionFilePolicyService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleSolutionUploadService.php';

class RiddleSolutionUploadServiceTest extends TestCase {
    public function testValidPdfIsUploadedAndAttached(): void {
        $attached = [];
        $result = (new RiddleSolutionUploadService())->process(
            42,
            ['name' => 'solution.pdf', 'size' => 1000, 'error' => 0],
            static function (array $file): array {
                self::assertSame('solution.pdf', $file['name']);
                return ['ext' => 'pdf', 'type' => 'application/pdf'];
            },
            static fn (): array => ['url' => '/solution.pdf', 'file' => '/tmp/solution.pdf'],
            static function (...$arguments) use (&$attached): int {
                $attached = $arguments;
                return 99;
            },
            static fn (): bool => false,
            static fn (): string => ''
        );

        $this->assertSame(['error' => null, 'message' => null, 'url' => '/solution.pdf'], $result);
        $this->assertSame([42, '/tmp/solution.pdf', 'solution.pdf', 'application/pdf'], $attached);
    }

    /**
     * @dataProvider invalidFileProvider
     */
    public function testInvalidFileStopsBeforeUpload(array $file, string $expectedError): void {
        $uploaded = false;
        $result = (new RiddleSolutionUploadService())->process(
            42,
            $file,
            static fn (): array => ['ext' => 'txt', 'type' => 'text/plain'],
            static function () use (&$uploaded): array {
                $uploaded = true;
                return [];
            },
            static fn (): int => 0,
            static fn (): bool => false,
            static fn (): string => ''
        );

        $this->assertSame($expectedError, $result['error']);
        $this->assertFalse($uploaded);
    }

    public function invalidFileProvider(): array {
        return [
            'missing transfer' => [[], 'missing_file'],
            'invalid type' => [['name' => 'solution.txt', 'size' => 100, 'error' => 0], 'invalid_file_type'],
        ];
    }
}
