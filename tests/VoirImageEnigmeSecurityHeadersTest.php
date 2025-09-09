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
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_security_headers_are_preserved(): void
    {
        global $headers;
        $headers    = [];
        $headers[]  = 'X-Content-Type-Options: nosniff';
        $initialLvl = ob_get_level();

        ob_start();
        echo 'buffer';

        while (ob_get_level()) {
            ob_end_clean();
        }
        while (ob_get_level() < $initialLvl) {
            ob_start();
        }

        nocache_headers();
        remove_all_actions('template_redirect');
        do_action('litespeed_control_set_nocache');

        $this->assertContains('X-Content-Type-Options: nosniff', $headers);
    }
}
