<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse-functions.php';

class ChasseWinnersTest extends TestCase
{
    public function test_chasse_install_winners_table_creates_table(): void
    {
        global $wpdb, $dbDeltaSql;
        $wpdb = new class {
            public string $prefix = 'wp_';
            public function get_charset_collate(): string
            {
                return 'utf8mb4_unicode_ci';
            }
        };
        $dbDeltaSql = '';
        chasse_install_winners_table();
        $this->assertStringContainsString('CREATE TABLE wp_chasse_winners', $dbDeltaSql);
        $this->assertStringContainsString('UNIQUE KEY user_chasse (user_id, chasse_id)', $dbDeltaSql);
        $this->assertStringContainsString('KEY chasse_id (chasse_id)', $dbDeltaSql);
    }

    public function test_enregistrer_gagnant_chasse_calls_replace(): void
    {
        global $wpdb;
        $wpdb = new class {
            public string $table = '';
            public array $data = [];
            public array $format = [];
            public string $prefix = 'wp_';
            public function replace($table, $data, $format): void
            {
                $this->table  = $table;
                $this->data   = $data;
                $this->format = $format;
            }
        };
        enregistrer_gagnant_chasse(5, 9, '2024-01-01 10:00:00');
        $this->assertSame('wp_chasse_winners', $wpdb->table);
        $this->assertSame(['user_id' => 5, 'chasse_id' => 9, 'date_win' => '2024-01-01 10:00:00'], $wpdb->data);
        $this->assertSame(['%d', '%d', '%s'], $wpdb->format);
    }

    public function test_compter_chasses_gagnees_with_multiple_hunts(): void
    {
        global $wpdb;
        $wpdb = new class {
            public string $prefix = 'wp_';
            public array $rows = [];
            public array $prepared = [];
            public function replace($table, $data, $format): void
            {
                $key = $data['user_id'] . '-' . $data['chasse_id'];
                $this->rows[$key] = $data;
            }
            public function prepare($query, ...$args)
            {
                $this->prepared = $args;
                return $query;
            }
            public function get_var($query)
            {
                $user_id = $this->prepared[0];
                $count = 0;
                foreach ($this->rows as $row) {
                    if ($row['user_id'] === $user_id) {
                        $count++;
                    }
                }
                return $count;
            }
        };
        enregistrer_gagnant_chasse(1, 101, '2024-01-01 00:00:00');
        enregistrer_gagnant_chasse(1, 102, '2024-01-02 00:00:00');
        enregistrer_gagnant_chasse(2, 103, '2024-01-03 00:00:00');
        enregistrer_gagnant_chasse(1, 102, '2024-01-04 00:00:00');
        $this->assertSame(2, compter_chasses_gagnees(1));
        $this->assertSame(1, compter_chasses_gagnees(2));
    }
}
