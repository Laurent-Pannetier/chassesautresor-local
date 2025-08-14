<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse/stats.php';

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

if (!function_exists('recuperer_ids_enigmes_pour_chasse')) {
    function recuperer_ids_enigmes_pour_chasse(int $chasse_id): array {
        return [10, 11];
    }
} else {
    $GLOBALS['enigme_ids'] = [10, 11];
}
if (!function_exists('get_the_title')) {
    function get_the_title($id) {
        return 'Enigme ' . $id;
    }
}
if (!function_exists('get_permalink')) {
    function get_permalink($id) {
        return 'http://example.com/' . $id;
    }
}

class ChasseParticipantsStatsTest extends TestCase {
    public function test_chasse_lister_participants_returns_engagements_and_resolutions(): void {
        global $wpdb;
        $wpdb = new class {
            public string $prefix = 'wp_';
            public string $users = 'wp_users';
            private int $call = 0;
            public function prepare($query, ...$args) { return $query; }
            public function get_results($query, $output = ARRAY_A) {
                $this->call++;
                if ($this->call === 1) {
                    return [
                        ['user_id' => 1, 'username' => 'alice', 'date_inscription' => '2024-01-01 10:00:00'],
                    ];
                }
                if ($this->call === 2) {
                    return [
                        ['user_id' => 1, 'enigme_id' => 10],
                        ['user_id' => 1, 'enigme_id' => 11],
                    ];
                }
                if ($this->call === 3) {
                    return [
                        ['user_id' => 1, 'enigme_id' => 10],
                    ];
                }
                return [];
            }
        };

        $res = chasse_lister_participants(5, 25, 0, 'inscription', 'ASC');
        $this->assertCount(1, $res);
        $first = $res[0];
        $this->assertSame(2, $first['nb_engagees']);
        $this->assertSame(1, $first['nb_resolues']);
        $this->assertCount(2, $first['enigmes']);
        $this->assertSame('Enigme 10', $first['enigmes'][0]['title']);
        $this->assertSame('http://example.com/10', $first['enigmes'][0]['url']);
    }
}
