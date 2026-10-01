<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CoreConstantsTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCoreDefinesThemeIndependentBusinessConstants(): void {
        require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Support/constants.php';

        self::assertSame('organisateur', ROLE_ORGANISATEUR);
        self::assertSame('organisateur_creation', ROLE_ORGANISATEUR_CREATION);
        self::assertSame('DESACTIVE', SOLUTION_STATE_DESACTIVE);
        self::assertFalse(CAT_DEBUG_VERBOSE);
    }
}
