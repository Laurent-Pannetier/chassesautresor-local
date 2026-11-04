<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\RiddleImageObscureNameService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageObscureNameService.php';

final class RiddleImageObscureNameServiceTest extends TestCase
{
    public function testPredictableNamesAreRecognized(): void
    {
        $service = new RiddleImageObscureNameService();

        self::assertFalse($service->isUnpredictable('image-1.jpg'));
        self::assertFalse($service->isUnpredictable('photo-1280x720.jpg'));
        self::assertTrue($service->isUnpredictable(str_repeat('a', 32) . '.jpg'));
        self::assertTrue($service->isUnpredictable(str_repeat('b', 32) . '-1280x720.webp'));
        self::assertTrue($service->isUnpredictable(str_repeat('c', 32) . '.jpg.webp'));
        self::assertTrue($service->isUnpredictable(str_repeat('d', 32) . '-scaled.jpg'));
    }

    public function testRenameFamilyKeepsTheStemAcrossSizesAndWebp(): void
    {
        $directory = sys_get_temp_dir() . '/cta-obscure-' . uniqid('', true);
        mkdir($directory);
        $bytes = 'drawing-v1';
        file_put_contents($directory . '/image-1.jpg', $bytes);
        file_put_contents($directory . '/image-1.webp', 'webp-v1');
        file_put_contents($directory . '/image-1-1280x1280.jpg', 'size');
        file_put_contents($directory . '/image-1-1280x1280.webp', 'size-webp');

        $service = new RiddleImageObscureNameService();
        $renamed = $service->renameFamily($directory . '/image-1.jpg');
        $stem = substr(hash('sha256', $bytes), 0, 32);

        self::assertSame($directory . '/' . $stem . '.jpg', $renamed);
        self::assertFileExists($directory . '/' . $stem . '.jpg');
        self::assertFileExists($directory . '/' . $stem . '.webp');
        self::assertFileExists($directory . '/' . $stem . '-1280x1280.jpg');
        self::assertFileExists($directory . '/' . $stem . '-1280x1280.webp');
        self::assertFileDoesNotExist($directory . '/image-1.jpg');
        self::assertSame($renamed, $service->renameFamily($renamed));
        self::assertTrue($service->riddleDirectoryIsUnpredictable($directory));

        $this->removeTree($directory);
    }

    public function testDirectoryObscureRenamesOrphanSizes(): void
    {
        $directory = sys_get_temp_dir() . '/cta-obscure-dir-' . uniqid('', true);
        mkdir($directory . '/etapes', 0777, true);
        file_put_contents($directory . '/etapes/planche.jpg', 'planche');
        file_put_contents($directory . '/etapes/planche-1600x1600.jpg', 'crop');

        $service = new RiddleImageObscureNameService();
        self::assertFalse($service->riddleDirectoryIsUnpredictable($directory));
        $service->obscureDirectory($directory);

        self::assertTrue($service->riddleDirectoryIsUnpredictable($directory));
        self::assertFileDoesNotExist($directory . '/etapes/planche.jpg');
        self::assertFileDoesNotExist($directory . '/etapes/planche-1600x1600.jpg');

        $this->removeTree($directory);
    }

    public function testIdenticalBytesReplaceThePredictableName(): void
    {
        $directory = sys_get_temp_dir() . '/cta-obscure-dup-' . uniqid('', true);
        mkdir($directory);
        $bytes = 'same-drawing';
        $stem = substr(hash('sha256', $bytes), 0, 32);
        file_put_contents($directory . '/' . $stem . '.jpg', $bytes);
        file_put_contents($directory . '/image-1.jpg', $bytes);

        $service = new RiddleImageObscureNameService();
        $renamed = $service->renameFamily($directory . '/image-1.jpg');

        self::assertSame($directory . '/' . $stem . '.jpg', $renamed);
        self::assertFileDoesNotExist($directory . '/image-1.jpg');
        self::assertSame($bytes, (string) file_get_contents($renamed));

        $this->removeTree($directory);
    }

    public function testMetadataBasenamesFollowTheNewStem(): void
    {
        $service = new RiddleImageObscureNameService();
        $stem = str_repeat('e', 32);
        $size = $service->renamedBasename('image-1-150x150.jpg', 'image-1.jpg', 'image-1', $stem);
        $webp = $service->renamedBasename('image-1.jpg.webp', 'image-1.jpg', 'image-1', $stem);

        self::assertSame($stem . '-150x150.jpg', $size);
        self::assertSame($stem . '.jpg.webp', $webp);
    }

    private function removeTree(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($directory);
    }
}
