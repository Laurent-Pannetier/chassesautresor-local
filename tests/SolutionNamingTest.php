<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('__')) {
    function __($text, $domain = null) { return $text; }
}
if (!function_exists('add_action')) {
    function add_action($hook, $callable, $priority = 10, $accepted_args = 1) {}
}
if (!function_exists('get_posts')) {
    function get_posts($args) { return [11, 12]; }
}
if (!function_exists('wp_update_post')) {
    function wp_update_post($args) { global $updated_posts; $updated_posts[] = $args; }
}
if (!function_exists('get_the_title')) {
    function get_the_title($id) { return 'Titre'; }
}
if (!function_exists('get_field')) {
    function get_field($key, $post_id) { return null; }
}

class SolutionNamingTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        global $updated_posts;
        $updated_posts = [];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_reordonner_solutions_formats_titles(): void
    {
        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        reordonner_solutions(5, 'chasse');

        global $updated_posts;
        $this->assertSame('Solution Titre #1', $updated_posts[0]['post_title']);
        $this->assertSame('Solution Titre #2', $updated_posts[1]['post_title']);
    }
}

