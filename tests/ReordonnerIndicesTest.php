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
            global $updated_posts;
            $updated_posts[] = $args;
            return true;
        }
    }
    if (!function_exists('__')) {
        function __($text, $domain = 'default')
        {
            return $text;
        }
    }
}

namespace ReordonnerIndicesTest {
    use PHPUnit\Framework\TestCase;

    class ReordonnerIndicesTest extends TestCase
    {
        protected function setUp(): void
        {
            global $updated_posts;
            $updated_posts = [];
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
    }
}
