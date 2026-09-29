<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class UserMessageRepositoryPluginCompatibilityTest extends TestCase
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
        require_once __DIR__
            . '/../wp-content/themes/chassesautresor/inc/messages/class-user-message-repository.php';

        $this->assertCoreRepositoryIsLoaded();
    }

    private function assertCoreRepositoryIsLoaded(): void
    {
        $reflection = new ReflectionClass('UserMessageRepository');

        self::assertSame(
            'ChassesAuTresor\\Core\\Messages\\UserMessageRepository',
            $reflection->getName()
        );
        self::assertStringContainsString(
            '/plugins/chassesautresor-core/src/Messages/UserMessageRepository.php',
            str_replace('\\', '/', (string) $reflection->getFileName())
        );
    }
}
