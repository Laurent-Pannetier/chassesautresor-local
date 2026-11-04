<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('nocache_headers')) {
    function nocache_headers(): void
    {
        global $headers;
        $headers[] = 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0';
        $headers[] = 'Pragma: no-cache';
        $headers[] = 'Expires: Wed, 11 Jan 1984 05:00:00 GMT';
    }
}

if (!function_exists('remove_all_actions')) {
    function remove_all_actions(string $hook): void
    {
        // no-op
    }
}

if (!function_exists('do_action')) {
    function do_action(string $hook): void
    {
        // no-op
    }
}

final class VoirImageEnigmeSecurityHeadersTest extends TestCase
{
    public function test_static_files_use_a_long_public_cache(): void
    {
        $rules = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageProtectionService.php'
        );

        $this->assertStringContainsString('public, max-age=31536000, immutable', $rules);
        $this->assertStringNotContainsString('Cache-Control: private', $rules);
        $this->assertStringNotContainsString('max-age=0', $rules);
        $this->assertStringContainsString('Options -Indexes', $rules);
    }
}
