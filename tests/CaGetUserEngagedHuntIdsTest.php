<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!class_exists('WP_User')) {
    class WP_User
    {
        public int $ID = 0;

        public string $user_login = '';

        public array $roles = [];

        public function __construct($data = 0)
        {
            if (is_object($data)) {
                $this->ID         = isset($data->ID) ? (int) $data->ID : 0;
                $this->user_login = isset($data->user_login) ? (string) $data->user_login : '';
            } elseif (is_array($data)) {
                $this->ID         = isset($data['ID']) ? (int) $data['ID'] : 0;
                $this->user_login = isset($data['user_login']) ? (string) $data['user_login'] : '';
            } elseif (is_int($data)) {
                $this->ID         = $data;
                $this->user_login = 'user' . $data;
            } else {
                $this->user_login = (string) $data;
            }
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
                static function ($registered_role) use ($role) {
                    return $registered_role !== $role;
                }
            ));
        }

        public function set_role($role): void
        {
            $this->roles = [$role];
        }
    }
}

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class CaGetUserEngagedHuntIdsTest extends TestCase
{
    public function test_demo_hunts_are_filtered_out(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/fixtures/');
        }

        if (!function_exists('apply_filters')) {
            function apply_filters($hook, $value, ...$args)
            {
                global $wp_filter;

                if (
                    isset($wp_filter[$hook])
                    && is_object($wp_filter[$hook])
                    && method_exists($wp_filter[$hook], 'apply_filters')
                ) {
                    $arguments = $args;
                    array_unshift($arguments, $value);

                    return $wp_filter[$hook]->apply_filters($value, $arguments);
                }

                return $value;
            }
        }

        if (!function_exists('get_post_status')) {
            function get_post_status($post_id)
            {
                return 'publish';
            }
        }

        if (!function_exists('get_field')) {
            function get_field($field, $post_id = null)
            {
                global $fields, $post_fields;

                return $fields[$post_id][$field] ?? $post_fields[$post_id][$field] ?? null;
            }
        }

        if (!function_exists('user_can')) {
            function user_can($user_id, $cap)
            {
                return false;
            }
        }

        if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
            function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id)
            {
                return false;
            }
        }

        if (!function_exists('chasse_est_visible_pour_utilisateur')) {
            function chasse_est_visible_pour_utilisateur($chasse_id, $user_id)
            {
                return true;
            }
        }

        if (!function_exists('get_organisateur_from_chasse')) {
            function get_organisateur_from_chasse($chasse_id)
            {
                return $chasse_id === 60 ? 5 : 10;
            }
        }

        if (!function_exists('get_user_by')) {
            function get_user_by($field, $value)
            {
                $login = ((int) $value === 42) ? 'demo' : 'regular';

                return new WP_User([
                    'ID'         => (int) $value,
                    'user_login' => $login,
                ]);
            }
        }

        global $fields, $post_fields;

        $fields = [
            5  => [
                'utilisateurs_associes'          => [
                    ['ID' => 42],
                ],
                'chasse_cache_statut_validation' => 'valide',
            ],
            10 => [
                'utilisateurs_associes'          => [
                    ['ID' => 84],
                ],
                'chasse_cache_statut_validation' => 'valide',
            ],
            60 => [
                'chasse_cache_statut_validation' => 'valide',
            ],
            70 => [
                'chasse_cache_statut_validation' => 'valide',
            ],
            80 => [
                'chasse_cache_statut_validation' => 'valide',
            ],
        ];

        $post_fields = [];

        $GLOBALS['wp_filter']['ca_demo_organisateur_logins'] = new class
        {
            public function apply_filters($value, $args)
            {
                $current = is_array($args[0] ?? null) ? $args[0] : [];
                $current[] = 'demo';

                return array_values(array_unique(array_filter(array_map(
                    static function ($login) {
                        return is_string($login) ? strtolower(trim($login)) : null;
                    },
                    $current
                ))));
            }
        };

        $GLOBALS['wp_filter']['ca_demo_is_demo_hunt'] = new class
        {
            public function apply_filters($value, $args)
            {
                $chasse_id = isset($args[1]) ? (int) $args[1] : 0;

                if (in_array($chasse_id, [60, 90], true)) {
                    return true;
                }

                return $value;
            }
        };

        $wpdb = new class
        {
            public string $prefix = 'wp_';

            public function prepare(string $query, int $user_id): string
            {
                return $query;
            }

            public function get_col(string $query): array
            {
                return [60, 70, 70, 80, 60];
            }
        };

        $GLOBALS['wpdb'] = $wpdb;

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/user-functions.php';

        $this->assertTrue(ca_demo_is_demo_hunt(60));
        $this->assertFalse(ca_demo_is_demo_hunt(70));

        $result = ca_get_user_engaged_hunt_ids(123);

        $this->assertSame([70, 80], $result);

        unset(
            $GLOBALS['wp_filter']['ca_demo_organisateur_logins'],
            $GLOBALS['wp_filter']['ca_demo_is_demo_hunt']
        );
        unset($GLOBALS['wpdb']);
    }
}
