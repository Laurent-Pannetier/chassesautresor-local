<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return $text;
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512)
    {
        return json_encode($data, $options);
    }
}

/**
 * @runTestsInSeparateProcesses
 */
class AddSiteMessageFailureTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/messages/class-user-message-repository.php';
        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/messages.php';

        global $wpdb;
        $wpdb = new FailingWpdb();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['cat_site_messages'] = [];
    }

    public function test_failed_insert_triggers_notice(): void
    {
        $errors = [];
        set_error_handler(function (int $errno, string $errstr) use (&$errors): bool {
            $errors[] = [$errno, $errstr];
            return true;
        });

        add_site_message('info', 'Hello', true);

        restore_error_handler();

        $this->assertNotEmpty($errors);
        $this->assertSame(E_USER_NOTICE, $errors[0][0]);
        $this->assertSame('Failed to insert site message', $errors[0][1]);
    }
}

class FailingWpdb
{
    public string $prefix = 'wp_';

    public string $last_error = '';

    public int $insert_id = 0;

    public function insert(string $table, array $data, array $format)
    {
        $this->insert_id = 0;

        return 0;
    }
}
