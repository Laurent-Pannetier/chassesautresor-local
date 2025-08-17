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
            public function prepare($query, ...$args) { return $query; }
            public function get_results($query, $output = ARRAY_A) {
                return [
                    [
                        'user_id' => 1,
                        'username' => 'alice',
                        'date_inscription' => '2024-01-01 10:00:00',
                        'nb_engagees' => 2,
                        'nb_resolues' => 1,
                    ],
                ];
            }
            public function get_col($query) {
                return [10, 11];
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

    public function test_chasse_lister_participants_sorts_by_participation_and_resolution(): void {
        global $wpdb;
        $wpdb = new class {
            public string $prefix = 'wp_';
            public string $users = 'wp_users';
            public function prepare($query, ...$args) { return $query; }
            public function get_results($query, $output = ARRAY_A) {
                if (stripos($query, 'ORDER BY nb_engagees DESC') !== false) {
                    return [
                        [
                            'user_id' => 1,
                            'username' => 'alice',
                            'date_inscription' => '2024-01-01 10:00:00',
                            'nb_engagees' => 2,
                            'nb_resolues' => 1,
                        ],
                        [
                            'user_id' => 2,
                            'username' => 'bob',
                            'date_inscription' => '2024-01-02 10:00:00',
                            'nb_engagees' => 1,
                            'nb_resolues' => 0,
                        ],
                    ];
                }
                if (stripos($query, 'ORDER BY nb_resolues ASC') !== false) {
                    return [
                        [
                            'user_id' => 2,
                            'username' => 'bob',
                            'date_inscription' => '2024-01-02 10:00:00',
                            'nb_engagees' => 1,
                            'nb_resolues' => 0,
                        ],
                        [
                            'user_id' => 1,
                            'username' => 'alice',
                            'date_inscription' => '2024-01-01 10:00:00',
                            'nb_engagees' => 2,
                            'nb_resolues' => 1,
                        ],
                    ];
                }
                return [];
            }
            public function get_col($query) {
                if (stripos($query, 'user_id = 1') !== false) {
                    return [10, 11];
                }
                if (stripos($query, 'user_id = 2') !== false) {
                    return [10];
                }
                return [];
            }
        };

        $res = chasse_lister_participants(5, 25, 0, 'participation', 'DESC');
        $this->assertSame('alice', $res[0]['username']);

        $res = chasse_lister_participants(5, 25, 0, 'resolution', 'ASC');
        $this->assertSame('bob', $res[0]['username']);
    }
}
