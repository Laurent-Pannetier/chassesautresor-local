<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in(): bool
    {
        return true;
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($id)
    {
        return $id === 123 ? 'solution' : 'chasse';
    }
}

if (!function_exists('get_field')) {
    function get_field($field, $post_id)
    {
        global $fields;
        return $fields[$field] ?? null;
    }
}

if (!function_exists('solution_action_autorisee')) {
    function solution_action_autorisee($action, $type, $id)
    {
        global $permission_args;
        $permission_args = [$action, $type, $id];
        return true;
    }
}

if (!function_exists('wp_delete_post')) {
    function wp_delete_post($id, $force = false)
    {
        global $deleted_id, $deleted_force;
        $deleted_id    = $id;
        $deleted_force = $force;
        return true;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null): void
    {
        throw new Exception((string) $data);
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null)
    {
        global $json_success;
        $json_success = $data;
        return $data;
    }
}

if (!function_exists('get_posts')) {
    function get_posts($args)
    {
        return [11, 12];
    }
}

if (!function_exists('wp_update_post')) {
    function wp_update_post($args)
    {
        global $updated_posts;
        $updated_posts[] = $args;
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($id)
    {
        return 'Titre';
    }
}

if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

final class SupprimerSolutionAjaxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        global $fields, $permission_args, $deleted_id, $deleted_force, $json_success, $updated_posts;
        $fields = [
            'solution_cible_type'   => 'enigme',
            'solution_enigme_linked' => 55,
        ];
        $permission_args = null;
        $deleted_id      = null;
        $deleted_force   = null;
        $json_success    = null;
        $updated_posts   = [];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_deletes_solution_after_permission_check(): void
    {
        global $permission_args, $deleted_id, $deleted_force, $json_success, $updated_posts;
        $_POST['solution_id'] = 123;

        supprimer_solution_ajax();

        $this->assertSame(['delete', 'enigme', 55], $permission_args);
        $this->assertSame(123, $deleted_id);
        $this->assertTrue($deleted_force);
        $this->assertNull($json_success);
        $this->assertSame('Solution Titre #1', $updated_posts[0]['post_title']);
        $this->assertSame('Solution Titre #2', $updated_posts[1]['post_title']);
    }
}

