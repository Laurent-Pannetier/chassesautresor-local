<?php
namespace {
    if (!function_exists('get_post_type')) {
        function get_post_type($id)
        {
            global $solution_post_type;

            return $solution_post_type ?? 'solution';
        }
    }

    if (!function_exists('update_field')) {
        function update_field($key, $value, $post_id): void
        {
            global $updated_solution_field;
            $updated_solution_field = compact('key', 'value', 'post_id');
        }
    }

    if (!function_exists('delete_post_meta')) {
        function delete_post_meta($id, $key): void
        {
            global $deleted_solution_meta;
            $deleted_solution_meta = compact('id', 'key');
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
    use ChassesAuTresor\Core\Content\SolutionPublicationService;

    class SolutionRendreAccessibleTest extends TestCase
    {
        protected function setUp(): void
        {
            global $solution_post_type, $updated_post, $updated_solution_field, $deleted_solution_meta;
            $solution_post_type = 'solution';
            $updated_post = null;
            $updated_solution_field = null;
            $deleted_solution_meta = null;
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_post_date_is_preserved_when_publishing(): void
        {
            global $updated_post, $updated_solution_field, $deleted_solution_meta;

            require_once __DIR__
                . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionPublicationService.php';
            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

            \solution_rendre_accessible(123);

            $this->assertSame(
                ['key' => 'solution_cache_etat_systeme', 'value' => 'EN_COURS', 'post_id' => 123],
                $updated_solution_field
            );
            $this->assertSame(
                ['id' => 123, 'key' => 'solution_date_disponibilite'],
                $deleted_solution_meta
            );
            $this->assertSame('2024-03-10 00:00:00', $updated_post['post_date']);
            $this->assertSame('2024-03-10 00:00:00', $updated_post['post_date_gmt']);
        }

        public function test_publication_service_ignores_non_solution_posts(): void
        {
            global $solution_post_type, $updated_solution_field;
            $solution_post_type = 'page';

            require_once __DIR__
                . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionPublicationService.php';

            SolutionPublicationService::makeAccessible(123);

            $this->assertNull($updated_solution_field);
        }
    }
}
