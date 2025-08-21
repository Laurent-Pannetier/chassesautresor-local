<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse/stats.php';

if (!function_exists('recuperer_ids_enigmes_pour_chasse')) {
    function recuperer_ids_enigmes_pour_chasse(int $chasse_id): array
    {
        return [10, 11];
    }
}


class ChasseStatsAggregateTest extends TestCase {
    public function test_chasse_compter_tentatives_aggregates_with_single_query(): void {
        global $wpdb;
        $wpdb = new class {
            public string $prefix = 'wp_';
            public int $get_var_calls = 0;
            public function prepare($query, ...$args) { return $query; }
            public function get_var($query) {
                $this->get_var_calls++;
                return 5;
            }
        };

        $res = chasse_compter_tentatives(1);
        $this->assertSame(5, $res);
        $this->assertSame(1, $wpdb->get_var_calls);
    }

    public function test_chasse_compter_points_collectes_aggregates_with_single_query(): void {
        global $wpdb;
        $wpdb = new class {
            public string $prefix = 'wp_';
            public int $get_var_calls = 0;
            public function prepare($query, ...$args) { return $query; }
            public function get_var($query) {
                $this->get_var_calls++;
                return 7;
            }
        };

        $res = chasse_compter_points_collectes(1);
        $this->assertSame(7, $res);
        $this->assertSame(1, $wpdb->get_var_calls);
    }

    public function test_chasse_calculer_taux_engagement_aggregates_once_for_enigmes(): void {
        global $wpdb;
        $wpdb = new class {
            public string $prefix = 'wp_';
            public int $get_var_calls = 0;
            public function prepare($query, ...$args) { return $query; }
            public function get_var($query) {
                $this->get_var_calls++;
                if ($this->get_var_calls === 1) {
                    return 2; // participants
                }
                return 6; // total engaged per riddle sum
            }
        };

        $res = chasse_calculer_taux_engagement(1);
        $this->assertSame(150.0, $res);
        $this->assertSame(2, $wpdb->get_var_calls);
    }

    public function test_chasse_calculer_taux_progression_aggregates_once_for_enigmes(): void {
        global $wpdb, $post_fields;
        $wpdb = new class {
            public string $prefix = 'wp_';
            public int $get_var_calls = 0;
            public function prepare($query, ...$args) { return $query; }
            public function get_var($query) {
                $this->get_var_calls++;
                if ($this->get_var_calls === 1) {
                    return 4; // total engaged per riddle sum
                }
                return 2; // total solved per riddle sum
            }
        };
        $post_fields = [
            10 => ['enigme_mode_validation' => 'manuelle'],
            11 => ['enigme_mode_validation' => 'manuelle'],
        ];
        $res = chasse_calculer_taux_progression(1);
        $this->assertSame(50.0, $res);
        $this->assertSame(2, $wpdb->get_var_calls);
    }
}
