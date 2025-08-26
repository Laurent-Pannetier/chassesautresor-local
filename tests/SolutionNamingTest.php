<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('__')) {
    function __($text, $domain = null) { return $text; }
}
if (!function_exists('add_action')) {
    function add_action($hook, $callable, $priority = 10, $accepted_args = 1) {}
}
if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() { return true; }
}
if (!function_exists('get_post_type')) {
    function get_post_type($id) { return $id === 5 ? 'chasse' : 'solution'; }
}
if (!function_exists('solution_action_autorisee')) {
    function solution_action_autorisee($a, $b, $c) { return true; }
}
if (!function_exists('get_current_user_id')) {
    function get_current_user_id() { return 1; }
}
if (!function_exists('wp_insert_post')) {
    function wp_insert_post($args) { return 11; }
}
if (!function_exists('wp_update_post')) {
    function wp_update_post($args) { global $updated_posts; $updated_posts[] = $args; }
}
if (!function_exists('update_field')) {
    function update_field($key, $value, $post_id) {}
}
if (!function_exists('get_posts')) {
    function get_posts($args) { return []; }
}
if (!function_exists('get_the_title')) {
    function get_the_title($id) { return 'Titre'; }
}
if (!class_exists('WP_Error')) {
    class WP_Error {
        public function __construct(private $code = '', private $message = '') {}
        public function get_error_message() { return $this->message; }
    }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) { return $thing instanceof WP_Error; }
}
if (!defined('TITRE_DEFAUT_SOLUTION')) {
    define('TITRE_DEFAUT_SOLUTION', 'solution');
}

class SolutionNamingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $updated_posts;
        $updated_posts = [];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_creer_solution_pour_objet_sets_title(): void
    {
        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        creer_solution_pour_objet(5, 'chasse');

        global $updated_posts;
        $this->assertSame('Solution | Titre', $updated_posts[0]['post_title']);
    }
}

