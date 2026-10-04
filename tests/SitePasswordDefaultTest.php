<?php

use PHPUnit\Framework\TestCase;

class SitePasswordDefaultTest extends TestCase
{
    public function test_default_site_password_is_citizen(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/inc/site-password.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "get_option('ca_site_password', 'citizen')",
            $source
        );
        $this->assertStringNotContainsString(
            "get_option('ca_site_password', 'rosebud')",
            $source
        );
    }
}
