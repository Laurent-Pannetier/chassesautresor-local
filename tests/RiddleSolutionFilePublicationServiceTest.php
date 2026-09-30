<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleSolutionFilePublicationService;
use PHPUnit\Framework\TestCase;

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p(string $directory): bool {
        return is_dir($directory) || mkdir($directory, 0777, true);
    }
}

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleSolutionFilePublicationService.php';

class RiddleSolutionFilePublicationServiceTest extends TestCase {
    private string $temporaryDirectory;

    protected function setUp(): void {
        $this->temporaryDirectory = sys_get_temp_dir() . '/cat-publish-' . uniqid('', true);
        mkdir($this->temporaryDirectory, 0777, true);
    }

    protected function tearDown(): void {
        @unlink($this->temporaryDirectory . '/solution.pdf');
        @unlink($this->temporaryDirectory . '/public/solution.pdf');
        @rmdir($this->temporaryDirectory . '/public');
        @rmdir($this->temporaryDirectory);
    }

    public function testFileIsMovedIntoPublicDirectory(): void {
        $source = $this->temporaryDirectory . '/solution.pdf';
        file_put_contents($source, 'PDF');

        $target = RiddleSolutionFilePublicationService::moveFile(
            $source,
            $this->temporaryDirectory . '/public'
        );

        $this->assertSame($this->temporaryDirectory . '/public/solution.pdf', $target);
        $this->assertFileDoesNotExist($source);
        $this->assertFileExists((string) $target);
    }

    public function testMissingSourceAndExistingTargetAreRejected(): void {
        $this->assertNull(RiddleSolutionFilePublicationService::moveFile(
            $this->temporaryDirectory . '/missing.pdf',
            $this->temporaryDirectory . '/public'
        ));

        mkdir($this->temporaryDirectory . '/public', 0777, true);
        $source = $this->temporaryDirectory . '/solution.pdf';
        $target = $this->temporaryDirectory . '/public/solution.pdf';
        file_put_contents($source, 'new');
        file_put_contents($target, 'existing');

        $this->assertNull(RiddleSolutionFilePublicationService::moveFile(
            $source,
            $this->temporaryDirectory . '/public'
        ));
        $this->assertSame('existing', file_get_contents($target));
    }
}
