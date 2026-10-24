<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SiteMessageSessionTest extends TestCase
{
    public function testVisitorsWithoutAPendingFlashDoNotOpenASession(): void
    {
        $source = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Messages/site-message-functions.php'
        );

        $this->assertStringNotContainsString('[get_site_messages] session démarrée', $source);
        $this->assertStringNotContainsString('bypass pour requête image', $source);
        $this->assertStringContainsString('cat_consume_session_messages', $source);
        $this->assertStringContainsString("isset(\$_COOKIE['cat_site_msg'])", $source);
    }
}
