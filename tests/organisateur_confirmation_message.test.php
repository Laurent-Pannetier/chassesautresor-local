<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return $text;
    }
}
if (!function_exists('add_action')) {
    function add_action(...$args): void {}
}
if (!function_exists('add_filter')) {
    function add_filter(...$args): void {}
}
if (!function_exists('current_time')) {
    function current_time(string $type)
    {
        return $type === 'mysql' ? '2023-01-01 00:00:00' : 0;
    }
}
if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data)
    {
        return json_encode($data);
    }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str)
    {
        return $str;
    }
}
if (!function_exists('get_query_var')) {
    function get_query_var($key)
    {
        return '1';
    }
}
if (!function_exists('is_page')) {
    function is_page($slug)
    {
        return false;
    }
}
if (!function_exists('confirmer_demande_organisateur')) {
    function confirmer_demande_organisateur($user_id, $token)
    {
        return 42;
    }
}
if (!function_exists('remove_site_message')) {
    function remove_site_message($key): void {}
}
if (!function_exists('wp_set_current_user')) {
    function wp_set_current_user($id): void {}
}
if (!function_exists('wp_set_auth_cookie')) {
    function wp_set_auth_cookie($id): void {}
}
if (!function_exists('get_permalink')) {
    function get_permalink($id)
    {
        return 'https://example.com/organisateur';
    }
}
if (!function_exists('add_query_arg')) {
    function add_query_arg($key, $value, $url)
    {
        return $url . '?' . $key . '=' . $value;
    }
}
class RedirectException extends Exception {}
if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect($url): void
    {
        throw new RedirectException($url);
    }
}
if (!function_exists('home_url')) {
    function home_url($path = '')
    {
        return 'https://example.com' . $path;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id(): int
    {
        return 1;
    }
}
if (!function_exists('current_user_can')) {
    function current_user_can(string $cap): bool
    {
        return false;
    }
}
if (!function_exists('est_organisateur')) {
    function est_organisateur(): bool
    {
        return false;
    }
}
if (!function_exists('get_organisateur_from_user')) {
    function get_organisateur_from_user(int $user_id): int
    {
        return 0;
    }
}
if (!function_exists('recuperer_enigmes_tentatives_en_attente')) {
    function recuperer_enigmes_tentatives_en_attente(int $id): array
    {
        return [];
    }
}
if (!function_exists('get_queried_object_id')) {
    function get_queried_object_id(): int
    {
        return 0;
    }
}
if (!function_exists('get_post_type')) {
    function get_post_type($id): string
    {
        return '';
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/messages/class-user-message-repository.php';
require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/user-functions.php';
require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/organisateur-functions.php';

class OrganisateurConfirmationMessageTest extends TestCase
{
    private DummyWpdb $wpdb;

    protected function setUp(): void
    {
        global $wpdb;
        $this->wpdb = new DummyWpdb();
        $wpdb       = $this->wpdb;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function test_confirmation_removes_persistent_message(): void
    {
        $expires = time() + 2 * DAY_IN_SECONDS;
        myaccount_add_persistent_message(
            1,
            'profil_verification',
            'Test',
            'info',
            true,
            0,
            false,
            null,
            null,
            $expires
        );

        $repo = new UserMessageRepository($this->wpdb);
        $this->assertNotEmpty($repo->get(1, 'persistent', false));

        $_GET['user']  = 1;
        $_GET['token'] = 'abc';
        try {
            traiter_confirmation_organisateur();
        } catch (RedirectException $e) {
            // Redirect expected.
        }

        $this->assertSame([], $repo->get(1, 'persistent', false));
    }

    public function test_profil_verification_message_has_close_button(): void
    {
        $expires = time() + 2 * DAY_IN_SECONDS;
        myaccount_add_persistent_message(
            1,
            'profil_verification',
            'Test',
            'info',
            true,
            0,
            false,
            null,
            null,
            $expires
        );

        $html = myaccount_get_important_messages();
        $this->assertStringContainsString('class="message-close" data-key="profil_verification"', $html);
        $this->assertStringContainsString('aria-label="Supprimer ce message"', $html);
    }
}

class DummyWpdb
{
    public string $prefix = 'wp_';
    public int $insert_id = 0;
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $data = [];

    public function insert(string $table, array $data, array $format): void
    {
        $this->insert_id++;
        $data['id']             = $this->insert_id;
        $this->data[$this->insert_id] = $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get_results(string $sql, $output)
    {
        return array_values($this->data);
    }

    public function delete(string $table, array $where, array $whereFormat): void
    {
        unset($this->data[$where['id']]);
    }

    public function query(string $sql): void
    {
        $now = current_time('mysql');
        $this->data = array_filter(
            $this->data,
            fn($r) => $r['expires_at'] === null || $r['expires_at'] >= $now
        );
    }
}
