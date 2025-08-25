<?php
namespace {
    if (!function_exists('get_post_type')) {
        function get_post_type($id)
        {
            return 'solution';
        }
    }

    if (!function_exists('update_field')) {
        function update_field($key, $value, $post_id): void
        {
        }
    }

    if (!function_exists('delete_post_meta')) {
        function delete_post_meta($id, $key): void
        {
        }
    }

    if (!function_exists('get_post_status')) {
        function get_post_status($id)
        {
            return 'draft';
        }
    }

    if (!function_exists('get_post')) {
        function get_post($id)
        {
            return (object) [
                'post_date'     => '2024-03-10 00:00:00',
                'post_date_gmt' => '2024-03-10 00:00:00',
            ];
        }
    }

    if (!function_exists('wp_update_post')) {
        function wp_update_post(array $data): void
        {
            global $updated_post;
            $updated_post = $data;
        }
    }

    if (!function_exists('add_action')) {
        function add_action($hook, $callable, $priority = 10, $accepted_args = 1): void
        {
        }
    }
}

namespace SolutionRendreAccessibleTest {
    use PHPUnit\Framework\TestCase;

    class SolutionRendreAccessibleTest extends TestCase
    {
        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_post_date_is_preserved_when_publishing(): void
        {
            global $updated_post;

            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

            \solution_rendre_accessible(123);

            $this->assertSame('2024-03-10 00:00:00', $updated_post['post_date']);
            $this->assertSame('2024-03-10 00:00:00', $updated_post['post_date_gmt']);
        }
    }
}

