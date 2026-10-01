<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PointServiceFunctionsTest extends TestCase {
    public function testDelegatesAllLegacyFactoriesToTheCoreFactory(): void {
        $source = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/point-service-functions.php'
        );

        self::assertStringContainsString('CoreServiceFactory::points($wpdb)', $source);
        self::assertStringContainsString('CoreServiceFactory::purchasePoints($wpdb)', $source);
        self::assertStringContainsString('CoreServiceFactory::conversion($wpdb)', $source);
    }
}
