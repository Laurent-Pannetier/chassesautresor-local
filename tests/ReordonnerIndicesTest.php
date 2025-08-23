<?php
namespace {
    if (!function_exists('get_posts')) {
        function get_posts($args)
        {
            return [10, 20, 30];
        }
    }
    if (!function_exists('wp_update_post')) {
        function wp_update_post($args)
        {
            global $updated_posts, $simulate_recursion;
            $updated_posts[] = $args;
            if (!empty($simulate_recursion)) {
                \reordonner_indices(5, 'chasse');
            }
            return true;
        }
    }
    if (!function_exists('__')) {
        function __($text, $domain = 'default')
        {
            return $text;
        }
    }
    if (!function_exists('get_field')) {
        function get_field($key, $post_id)
        {
            if ($key === 'indice_cible_type') {
                return 'chasse';
            }
            if ($key === 'indice_chasse_linked') {
                return 5;
            }

            return null;
        }
    }
    if (!function_exists('wp_is_post_revision')) {
        function wp_is_post_revision($post_id)
        {
            return false;
        }
    }
    if (!function_exists('wp_is_post_autosave')) {
        function wp_is_post_autosave($post_id)
        {
            return false;
        }
    }
}

namespace ReordonnerIndicesTest {
    use PHPUnit\Framework\TestCase;

    class ReordonnerIndicesTest extends TestCase
    {
        protected function setUp(): void
        {
            global $updated_posts, $simulate_recursion;
            $updated_posts      = [];
            $simulate_recursion = 0;
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_renames_indices_sequentially(): void
        {
            global $updated_posts;
            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            \reordonner_indices(5, 'chasse');

            $this->assertCount(3, $updated_posts);
            $this->assertSame(['ID' => 10, 'post_title' => 'Indice #1'], $updated_posts[0]);
            $this->assertSame(['ID' => 20, 'post_title' => 'Indice #2'], $updated_posts[1]);
            $this->assertSame(['ID' => 30, 'post_title' => 'Indice #3'], $updated_posts[2]);
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_reorders_after_permanent_deletion(): void
        {
            global $updated_posts, $indice_delete_context;
            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            $indice_delete_context = ['id' => 5, 'type' => 'chasse'];
            \reordonner_indices_apres_suppression(99);

            $this->assertCount(3, $updated_posts);
            $this->assertSame(['ID' => 10, 'post_title' => 'Indice #1'], $updated_posts[0]);
            $this->assertSame(['ID' => 20, 'post_title' => 'Indice #2'], $updated_posts[1]);
            $this->assertSame(['ID' => 30, 'post_title' => 'Indice #3'], $updated_posts[2]);
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_prevents_recursive_reordering(): void
        {
            global $updated_posts, $simulate_recursion;
            $simulate_recursion = 1;
            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            \reordonner_indices(5, 'chasse');

            $this->assertCount(3, $updated_posts);
            $this->assertSame(['ID' => 10, 'post_title' => 'Indice #1'], $updated_posts[0]);
            $this->assertSame(['ID' => 20, 'post_title' => 'Indice #2'], $updated_posts[1]);
            $this->assertSame(['ID' => 30, 'post_title' => 'Indice #3'], $updated_posts[2]);
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_reorders_after_save_post(): void
        {
            global $updated_posts;
            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            \reordonner_indices_apres_enregistrement(99);

            $this->assertCount(3, $updated_posts);
            $this->assertSame(['ID' => 10, 'post_title' => 'Indice #1'], $updated_posts[0]);
            $this->assertSame(['ID' => 20, 'post_title' => 'Indice #2'], $updated_posts[1]);
            $this->assertSame(['ID' => 30, 'post_title' => 'Indice #3'], $updated_posts[2]);
        }
    }
}
