<?php

use PHPUnit\Framework\TestCase;

if (!defined('ROLE_ORGANISATEUR_CREATION')) {
    define('ROLE_ORGANISATEUR_CREATION', 'organisateur_creation');
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

if (!function_exists('get_user_meta')) {
    function get_user_meta($user_id, $key, $single = false)
    {
        global $cat_test_user_meta;
        return $cat_test_user_meta[$user_id][$key] ?? '';
    }
}

if (!function_exists('delete_user_meta')) {
    function delete_user_meta($user_id, $key): void
    {
        global $cat_test_user_meta;
        unset($cat_test_user_meta[$user_id][$key]);
    }
}

if (!function_exists('creer_organisateur_pour_utilisateur')) {
    function creer_organisateur_pour_utilisateur($user_id)
    {
        return 123;
    }
}

if (!function_exists('current_time')) {
    function current_time(string $type)
    {
        return $type === 'mysql' ? gmdate('Y-m-d H:i:s', time()) : time();
    }
}

if (!class_exists('WP_User')) {
    class WP_User
    {
        public int $ID;
        public array $roles = [];

        public function __construct(int $ID)
        {
            $this->ID = $ID;
        }

        public function add_role($role): void
        {
            $this->roles[] = $role;
        }
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/organisateur-functions.php';

class OrganisateurConfirmationTest extends TestCase
{
    protected function setUp(): void
    {
        global $cat_test_user_meta;
        $cat_test_user_meta = [];
    }

    public function test_confirmer_demande_organisateur_token_expire(): void
    {
        global $cat_test_user_meta;
        $user_id = 1;
        $now = strtotime(current_time('mysql'));
        $cat_test_user_meta[$user_id] = [
            'organisateur_demande_token' => 'abc',
            'organisateur_demande_date' => gmdate('Y-m-d H:i:s', $now - 3 * DAY_IN_SECONDS),
        ];
        $this->assertNull(confirmer_demande_organisateur($user_id, 'abc'));
    }

    public function test_confirmer_demande_organisateur_token_valide(): void
    {
        global $cat_test_user_meta;
        $user_id = 2;
        $now = strtotime(current_time('mysql'));
        $cat_test_user_meta[$user_id] = [
            'organisateur_demande_token' => 'def',
            'organisateur_demande_date' => gmdate('Y-m-d H:i:s', $now - DAY_IN_SECONDS),
        ];
        $this->assertSame(123, confirmer_demande_organisateur($user_id, 'def'));
    }
}

