<?php
use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

$test_fields = [];
$updated_fields = [];
$winner_log = [];
$enigmes_associees = [];

function get_field($field, $id) {
    global $test_fields;
    return $test_fields[$field . '_' . $id] ?? null;
}

function update_field($field, $value, $id) {
    global $updated_fields;
    $updated_fields[$field . '_' . $id] = $value;
}

if (!function_exists('enregistrer_gagnant_chasse')) {
    function enregistrer_gagnant_chasse(int $user_id, int $chasse_id, string $date_win): void
    {
        global $winner_log;
        $winner_log[] = [$user_id, $chasse_id, $date_win];
    }
}

function recuperer_id_chasse_associee($enigme_id) {
    return 777;
}

function recuperer_enigmes_associees($chasse_id): array {
    global $enigmes_associees;
    return $enigmes_associees;
}

function get_userdata($user_id) {
    return (object) ['display_name' => 'Alice', 'user_login' => 'alice'];
}

function current_time($type) {
    if ($type === 'mysql') {
        return '2024-01-01 00:00:00';
    }
    return '2024-01-01';
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/gamify-functions.php';

class FinDeChasseAutomatiqueTest extends TestCase
{
    public function test_enregistrement_gagnant_et_chasse_terminee(): void
    {
        global $wpdb, $test_fields, $updated_fields, $winner_log, $enigmes_associees;

        $wpdb = new class {
            public string $prefix = 'wp_';
            public bool $replace_called = false;
            public function get_var($query)
            {
                return 1;
            }
            public function prepare($query, ...$args)
            {
                $params = $args[0] ?? [];
                if (is_array($params) && count($args) === 1) {
                    $args = $params;
                }
                if (!$args) {
                    return $query;
                }
                return vsprintf($query, $args);
            }
            public function replace($table, $data, $format)
            {
                $this->replace_called = true;
                return 1;
            }
        };

        $test_fields = [
            'chasse_mode_fin_777' => 'automatique',
            'chasse_infos_nb_max_gagants_777' => 1,
            'chasse_cache_gagnants_777' => '',
            'enigme_mode_validation_1001' => 'automatique',
            'enigme_mode_validation_1002' => 'aucune',
        ];
        $updated_fields = [];
        $winner_log = [];
        $enigmes_associees = [1001, 1002];

        verifier_fin_de_chasse(5, 1002);

        $this->assertTrue($wpdb->replace_called);
        $this->assertSame('termine', $updated_fields['chasse_cache_statut_777']);
    }
}
