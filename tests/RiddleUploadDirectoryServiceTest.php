<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\RiddleUploadDirectoryService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleUploadDirectoryService.php';

class RiddleUploadDirectoryServiceTest extends TestCase {
    private string $temporaryDirectory;
    private RiddleUploadDirectoryService $service;

    protected function setUp(): void {
        $this->temporaryDirectory = sys_get_temp_dir() . '/cat-riddle-' . uniqid('', true);
        $this->service = new RiddleUploadDirectoryService();
    }

    protected function tearDown(): void {
        @unlink($this->temporaryDirectory . '/_enigmes/enigme-12/nested/file.txt');
        @rmdir($this->temporaryDirectory . '/_enigmes/enigme-12/nested');
        @rmdir($this->temporaryDirectory . '/_enigmes/enigme-12');
        @rmdir($this->temporaryDirectory . '/_enigmes');
        @rmdir($this->temporaryDirectory);
    }

    public function testDirectoryPathRejectsInvalidInputs(): void {
        $this->assertNull($this->service->getDirectoryPath(0, '/uploads'));
        $this->assertNull($this->service->getDirectoryPath(12, ''));
        $this->assertSame('/uploads/_enigmes/enigme-12', $this->service->getDirectoryPath(12, '/uploads/'));
    }

    public function testNestedRiddleDirectoryIsDeletedRecursively(): void {
        $directory = $this->temporaryDirectory . '/_enigmes/enigme-12/nested';
        mkdir($directory, 0777, true);
        file_put_contents($directory . '/file.txt', 'content');

        $this->assertTrue($this->service->delete(12, $this->temporaryDirectory));
        $this->assertDirectoryDoesNotExist($this->temporaryDirectory . '/_enigmes/enigme-12');
    }

    public function testMissingDirectoryIsIgnored(): void {
        $this->assertFalse($this->service->delete(12, $this->temporaryDirectory));
    }
}
