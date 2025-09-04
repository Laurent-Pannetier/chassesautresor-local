<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return $text;
    }
}

class MyAccountMessagesTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_validation_message_is_regenerated(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }

        eval('function is_user_logged_in(){return true;}');
        eval('function is_singular($types){return in_array("chasse", (array)$types, true);}');
        eval('function get_queried_object_id(){return 123;}');
        eval('function get_post_type($id){return "chasse";}');
        eval('function verifier_ou_mettre_a_jour_cache_complet($id){}');
        eval('function get_current_user_id(){return 1;}');
        eval('function peut_valider_chasse($cid,$uid){return true;}');
        eval('function get_permalink($id){return "https://example.com/post-{$id}";}');
        eval('function esc_url($url){return $url;}');
        eval('function add_action($h,$c){}');
        eval('function wp_json_encode($data,$options=0,$depth=512){return json_encode($data,$options);}');
        eval('function current_time($type){return $type=="mysql"?"2023-01-01 00:00:00":0;}');

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/messages/class-user-message-repository.php';
        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/user-functions.php';

        global $wpdb;
        $wpdb = new DummyWpdb();

        $repo = new UserMessageRepository($wpdb);
        $repo->insert(
            1,
            json_encode([
                'key'         => 'correction_info_chasse_123',
                'text'        => 'Ancien',
                'type'        => 'info',
                'dismissible' => false,
            ]),
            'persistent',
            null,
            null
        );

        myaccount_maybe_add_validation_message();

        $rows = $repo->get(1, 'persistent', null);
        $this->assertCount(1, $rows);
        $data = json_decode($rows[0]['message'], true);
        $this->assertSame(123, $data['chasse_scope']);
        $this->assertArrayHasKey('include_enigmes', $data);
        $this->assertTrue($data['include_enigmes']);

        $messages = myaccount_get_persistent_messages(1);
        $keys = array_map(static fn($m) => $m['key'], $messages);
        $this->assertContains('correction_info_chasse_123', $keys);
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
        $data['id']              = $this->insert_id;
        $this->data[$this->insert_id] = $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get_results(string $sql, $output): array
    {
        $rows = array_values($this->data);
        if (preg_match('/user_id = (\d+)/', $sql, $m)) {
            $rows = array_filter(
                $rows,
                static fn($r) => (int) $r['user_id'] === (int) $m[1]
            );
        }

        return array_values($rows);
    }

    public function prepare(string $query, array $params): string
    {
        $placeholders = array_map(
            static fn($p) => is_int($p) ? $p : "'{$p}'",
            $params
        );

        return vsprintf($query, $placeholders);
    }

    public function delete(string $table, array $where, array $whereFormat): void
    {
        unset($this->data[$where['id']]);
    }
}

