<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class PointsRepositoryPluginCompatibilityTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCorePluginExposesLegacyRepositoryClass(): void
    {
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/chassesautresor-core.php';

        $this->assertCoreRepositoryIsLoaded();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testThemeCompatibilityLoaderUsesCorePluginRepository(): void
    {
        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/PointsRepository.php';

        $this->assertCoreRepositoryIsLoaded();
    }

    private function assertCoreRepositoryIsLoaded(): void
    {
        $reflection = new ReflectionClass('PointsRepository');

        self::assertSame(
            'ChassesAuTresor\\Core\\Points\\PointsRepository',
            $reflection->getName()
        );
        self::assertStringContainsString(
            '/plugins/chassesautresor-core/src/Points/PointsRepository.php',
            str_replace('\\', '/', (string) $reflection->getFileName())
        );
    }
}
