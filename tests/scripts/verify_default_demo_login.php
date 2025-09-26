<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../fixtures/');
}

require_once __DIR__ . '/../../wp-content/themes/chassesautresor/inc/constants.php';

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
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args)
    {
        return $value;
    }
}

if (!function_exists('add_action')) {
    function add_action(...$args): void
    {
    }
}

if (!function_exists('add_filter')) {
    function add_filter(...$args): void
    {
    }
}

if (!function_exists('get_field')) {
    function get_field($field, $post_id = null)
    {
        global $fields, $post_fields;

        return $fields[$post_id][$field] ?? $post_fields[$post_id][$field] ?? null;
    }
}

if (!function_exists('get_organisateur_from_chasse')) {
    function get_organisateur_from_chasse($chasse_id)
    {
        return 200;
    }
}

require_once __DIR__ . '/../../wp-content/themes/chassesautresor/inc/chasse/demo.php';

global $fields, $post_fields;

$fields = [
    200 => [
        'utilisateurs_associes'          => [
            new WP_User([
                'ID'         => 101,
                'user_login' => 'organisateur1',
            ]),
        ],
        'chasse_cache_statut_validation' => 'valide',
    ],
    100 => [
        'chasse_cache_statut_validation' => 'valide',
    ],
];

$post_fields = [];

if (CA_DEMO_ORGANISATEUR_LOGINS !== ['organisateur1']) {
    fwrite(STDERR, 'Unexpected default logins: ' . json_encode(CA_DEMO_ORGANISATEUR_LOGINS));
    exit(1);
}

if (!ca_demo_is_demo_hunt(100)) {
    fwrite(STDERR, 'ca_demo_is_demo_hunt returned false');
    exit(1);
}

exit(0);
