<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleSolutionFileStorageService;
use PHPUnit\Framework\TestCase;

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p(string $directory): bool {
        return is_dir($directory) || mkdir($directory, 0777, true);
    }
}
if (!function_exists('__')) {
    function __(string $message, string $domain): string {
        return $message;
    }
}

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleSolutionFileStorageService.php';

class RiddleSolutionFileStorageServiceTest extends TestCase {
    private string $temporaryDirectory;

    protected function setUp(): void {
        $this->temporaryDirectory = sys_get_temp_dir() . '/cat-solution-' . uniqid('', true);
    }

    protected function tearDown(): void {
        $accessControlFile = $this->temporaryDirectory . '/protected/solutions/.htaccess';
        if (is_file($accessControlFile)) {
            unlink($accessControlFile);
        }
        @rmdir($this->temporaryDirectory . '/protected/solutions');
        @rmdir($this->temporaryDirectory . '/protected');
        @rmdir($this->temporaryDirectory);
    }

    public function testProtectedDirectoryHasNoPublicUrlAndDeniesWebAccess(): void {
        $directories = (new RiddleSolutionFileStorageService())->prepareUploadDirectory(
            ['url' => 'https://example.com/uploads', 'baseurl' => 'https://example.com/uploads'],
            $this->temporaryDirectory
        );

        $expectedPath = $this->temporaryDirectory . '/protected/solutions';
        $this->assertSame($expectedPath, $directories['path']);
        $this->assertSame($expectedPath, $directories['basedir']);
        $this->assertSame('', $directories['url']);
        $this->assertSame('', $directories['baseurl']);
        $this->assertStringContainsString(
            'Require all denied',
            (string) file_get_contents($expectedPath . '/.htaccess')
        );
    }

    public function testIncompleteAccessControlFileIsRepaired(): void {
        $protectedDirectory = $this->temporaryDirectory . '/protected/solutions';
        mkdir($protectedDirectory, 0777, true);
        file_put_contents($protectedDirectory . '/.htaccess', 'Allow from all');

        (new RiddleSolutionFileStorageService())->prepareUploadDirectory(
            [],
            $this->temporaryDirectory
        );

        $accessControl = (string) file_get_contents($protectedDirectory . '/.htaccess');
        $this->assertStringNotContainsString('Allow from all', $accessControl);
        $this->assertStringContainsString('Deny from all', $accessControl);
        $this->assertStringContainsString('Require all denied', $accessControl);
    }
}
