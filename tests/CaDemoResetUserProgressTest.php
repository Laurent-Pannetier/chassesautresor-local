<?php

namespace CaDemoResetUserProgressTest;

use PHPUnit\Framework\TestCase;

class CaDemoResetUserProgressTest extends TestCase
{
    private function bootstrapHelpers(): void
    {
        if (!defined('CA_DEMO_ORGANISATEUR_LOGINS')) {
            define('CA_DEMO_ORGANISATEUR_LOGINS', []);
        }

        $codeBlocks = [
            <<<'PHP'
namespace {
    if (!class_exists('WP_Error')) {
        class WP_Error
        {
            private string $code;
            private string $message;

            public function __construct(string $code = '', string $message = '', $data = null)
            {
                $this->code    = $code;
                $this->message = $message;
            }

            public function get_error_code(): string
            {
                return $this->code;
            }

            public function get_error_message($code = ''): string
            {
                return $this->message;
            }
        }
    }

    if (!function_exists('is_wp_error')) {
        function is_wp_error($thing): bool
        {
            return $thing instanceof WP_Error;
        }
    }

    if (!function_exists('cat_debug')) {
        function cat_debug($message): void
        {
            global $cat_debug_logs;

            if (!is_array($cat_debug_logs ?? null)) {
                $cat_debug_logs = [];
            }

            $cat_debug_logs[] = $message;
        }
    }

    if (!function_exists('__')) {
        function __($text, $domain = null)
        {
            return (string) $text;
        }
    }
}
PHP
            ,
            <<<'PHP'
namespace {
    if (!class_exists('wpdb')) {
        class wpdb
        {
            public string $prefix = 'wp_';
            public string $usermeta = 'wp_usermeta';
            public array $deleted_rows = [];
            public array $queries = [];
            private array $mockResults = [];
            private array $mockCols = [];

            public function prepare(string $query, ...$args): array
            {
                if (count($args) === 1 && is_array($args[0])) {
                    $args = $args[0];
                }

                return [
                    'query' => $query,
                    'args'  => array_values($args),
                ];
            }

            private function buildKey($prepared): string
            {
                if (is_array($prepared) && isset($prepared['query'])) {
                    return $prepared['query'] . '|' . json_encode($prepared['args']);
                }

                return (string) $prepared;
            }

            public function register_results(string $query, array $args, array $results): void
            {
                $this->mockResults[$this->buildKey(['query' => $query, 'args' => array_values($args)])] = $results;
            }

            public function register_cols(string $query, array $args, array $cols): void
            {
                $this->mockCols[$this->buildKey(['query' => $query, 'args' => array_values($args)])] = $cols;
            }

            public function delete(string $table, array $where, array $formats = []): int
            {
                $this->deleted_rows[] = [
                    'table'   => $table,
                    'where'   => $where,
                    'formats' => $formats,
                ];

                return 1;
            }

            public function get_col($prepared): array
            {
                return $this->mockCols[$this->buildKey($prepared)] ?? [];
            }

            public function get_results($prepared): array
            {
                return $this->mockResults[$this->buildKey($prepared)] ?? [];
            }

            public function query($prepared): bool
            {
                $this->queries[] = $this->buildKey($prepared);

                return true;
            }
        }
    }
}
PHP
            ,
            <<<'PHP'
namespace {
    if (!class_exists('WP_User')) {
        class WP_User
        {
            public int $ID;
            public array $roles = [];
            public string $display_name = '';
            public string $user_login = '';

            public function __construct(int $id)
            {
                $this->ID = $id;
                $this->display_name = 'Player ' . $id;
                $this->user_login = 'player' . $id;
            }

            public function add_role($role): void
            {
                if (!in_array($role, $this->roles, true)) {
                    $this->roles[] = $role;
                }
            }

            public function remove_role($role): void
            {
                $this->roles = array_values(array_filter(
                    $this->roles,
                    static function ($registeredRole) use ($role) {
                        return $registeredRole !== $role;
                    }
                ));
            }

            public function set_role($role): void
            {
                $this->roles = [$role];
            }
        }
    }
}
PHP
            ,
            <<<'PHP'
namespace {
    if (!function_exists('get_organisateur_from_chasse')) {
        function get_organisateur_from_chasse($chasseId)
        {
            return 321;
        }
    }

    if (!function_exists('get_field')) {
        function get_field($field, $postId = null)
        {
            global $mock_fields;

            if ('utilisateurs_associes' === $field) {
                return [new WP_User(42)];
            }

            return $mock_fields[$postId][$field] ?? null;
        }
    }

    if (!function_exists('get_user_by')) {
        function get_user_by($field, $value)
        {
            return new WP_User((int) $value);
        }
    }

    if (!function_exists('get_userdata')) {
        function get_userdata($userId)
        {
            return new WP_User((int) $userId);
        }
    }

    if (!function_exists('recuperer_enigmes_associees')) {
        function recuperer_enigmes_associees(int $chasseId): array
        {
            global $enigmes_map;

            return $enigmes_map[$chasseId] ?? [];
        }
    }

    if (!function_exists('update_field')) {
        function update_field($name, $value, $postId)
        {
            global $updated_fields, $updated_fields_log;

            if (!is_array($updated_fields ?? null)) {
                $updated_fields = [];
            }

            if (!is_array($updated_fields_log ?? null)) {
                $updated_fields_log = [];
            }

            $updated_fields[$name] = $value;
            $updated_fields_log[] = [$name, $value, $postId];

            return true;
        }
    }

    if (!function_exists('delete_field')) {
        function delete_field($name, $postId)
        {
            global $deleted_fields;
            $deleted_fields[] = [$name, $postId];

            return true;
        }
    }

    if (!function_exists('delete_user_meta')) {
        function delete_user_meta($userId, $key)
        {
            global $deleted_user_meta;
            $deleted_user_meta[] = [$userId, $key];

            return true;
        }
    }

    if (!function_exists('clean_user_cache')) {
        function clean_user_cache($userId)
        {
            global $cleaned_user_cache;
            $cleaned_user_cache[] = $userId;
        }
    }
}
PHP
            ,
            <<<'PHP'
namespace {
    if (!function_exists('mettre_a_jour_statuts_chasse')) {
        function mettre_a_jour_statuts_chasse($chasseId)
        {
            global $updated_statuses;
            $updated_statuses[] = $chasseId;
        }
    }

    if (!function_exists('chasse_clear_infos_affichage_cache')) {
        function chasse_clear_infos_affichage_cache($chasseId)
        {
            global $cleared_chasse_cache;
            $cleared_chasse_cache[] = $chasseId;
        }
    }

    if (!function_exists('enigme_clear_sidebar_cache')) {
        function enigme_clear_sidebar_cache($chasseId, $userId)
        {
            global $cleared_sidebar_cache;
            $cleared_sidebar_cache[] = [$chasseId, $userId];
        }
    }

    if (!function_exists('utilisateur_est_engage_dans_chasse')) {
        function utilisateur_est_engage_dans_chasse($userId, $chasseId)
        {
            global $engaged_users;
            $key = $chasseId . ':' . $userId;

            return !empty($engaged_users[$key]);
        }
    }

    if (!function_exists('is_user_logged_in')) {
        function is_user_logged_in()
        {
            return true;
        }
    }

    if (!function_exists('get_current_user_id')) {
        function get_current_user_id()
        {
            return 23;
        }
    }

    if (!function_exists('wp_verify_nonce')) {
        function wp_verify_nonce($nonce, $action)
        {
            return $nonce === $action;
        }
    }

    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($value)
        {
            return (string) $value;
        }
    }

    if (!function_exists('wp_unslash')) {
        function wp_unslash($value)
        {
            return $value;
        }
    }

    if (!function_exists('wp_send_json_error')) {
        function wp_send_json_error($data)
        {
            throw new \RuntimeException('ajax-error:' . json_encode($data));
        }
    }

    if (!function_exists('wp_send_json_success')) {
        function wp_send_json_success($data)
        {
            throw new \RuntimeException('ajax-success:' . json_encode($data));
        }
    }
}
PHP
        ];

        foreach ($codeBlocks as $block) {
            eval($block);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_resets_tables_and_metas(): void
    {
        $this->bootstrapHelpers();

        global $wpdb, $mock_fields, $enigmes_map, $updated_fields, $updated_fields_log, $deleted_fields,
            $deleted_user_meta, $cleaned_user_cache, $updated_statuses, $cleared_chasse_cache,
            $cleared_sidebar_cache, $force_demo_overrides, $engaged_users;

        $wpdb = new \wpdb();
        $mock_fields = [
            15 => [
                'chasse_cache_gagnants'        => 'Player 23',
                'chasse_cache_date_decouverte' => '2024-01-01 10:00:00',
                'chasse_cache_complet'         => 1,
            ],
        ];
        $enigmes_map = [15 => [101, 102]];
        $updated_fields = [];
        $updated_fields_log = [];
        $deleted_fields = [];
        $deleted_user_meta = [];
        $cleaned_user_cache = [];
        $updated_statuses = [];
        $cleared_chasse_cache = [];
        $cleared_sidebar_cache = [];
        $force_demo_overrides = [15 => true];
        $engaged_users = ['15:23' => true];

        $wpdb->register_results(
            'SELECT user_id, date_win FROM wp_chasse_winners WHERE chasse_id = %d ORDER BY date_win ASC',
            [15],
            []
        );
        $wpdb->register_cols(
            'SELECT DISTINCT indice_id FROM wp_indices_deblocages WHERE user_id = %d AND chasse_id = %d',
            [23, 15],
            [301]
        );
        $wpdb->register_cols(
            'SELECT DISTINCT indice_id FROM wp_indices_deblocages WHERE user_id = %d AND enigme_id IN (%d,%d)',
            [23, 101, 102],
            [302]
        );

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse/demo.php';

        $result = \ca_demo_reset_user_progress(15, 23);

        $this->assertTrue($result);

        $tables = array_column($wpdb->deleted_rows, 'table');
        $this->assertContains('wp_chasse_winners', $tables);
        $this->assertContains('wp_engagements', $tables);
        $hasIndicesDelete = false;
        foreach ($wpdb->queries as $query) {
            if (strpos($query, 'wp_indices_deblocages') !== false) {
                $hasIndicesDelete = true;
                break;
            }
        }

        $this->assertTrue($hasIndicesDelete);

        $this->assertContains(['chasse_cache_gagnants', '', 15], $updated_fields_log);
        $this->assertContains(['chasse_cache_complet', 0, 15], $updated_fields_log);
        $this->assertContains(['chasse_cache_date_decouverte', 15], $deleted_fields);

        $expectedMeta = [
            [23, 'souscription_chasse_15'],
            [23, 'statut_enigme_101'],
            [23, 'statut_enigme_102'],
            [23, 'enigme_101_resolution_date'],
            [23, 'enigme_102_resolution_date'],
            [23, 'indice_debloque_301'],
            [23, 'indice_debloque_302'],
        ];
        foreach ($expectedMeta as $meta) {
            $this->assertContains($meta, $deleted_user_meta);
        }

        $this->assertSame([23], $cleaned_user_cache);
        $this->assertSame([15], $updated_statuses);
        $this->assertSame([15], $cleared_chasse_cache);
        $this->assertContains([15, 23], $cleared_sidebar_cache);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_returns_error_when_not_demo(): void
    {
        $this->bootstrapHelpers();

        global $wpdb, $force_demo_overrides, $cat_debug_logs;

        $wpdb = new \wpdb();
        $force_demo_overrides = [42 => false];
        $cat_debug_logs = [];

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse/demo.php';

        $result = \ca_demo_reset_user_progress(42, 23);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('not_demo', $result->get_error_code());
        $this->assertNotEmpty($cat_debug_logs);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_returns_error_when_wpdb_missing(): void
    {
        $this->bootstrapHelpers();

        global $force_demo_overrides, $cat_debug_logs;

        $force_demo_overrides = [77 => true];
        $cat_debug_logs = [];

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse/demo.php';

        $result = \ca_demo_reset_user_progress(77, 23);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('wpdb_missing', $result->get_error_code());
        $this->assertNotEmpty($cat_debug_logs);
    }
}
