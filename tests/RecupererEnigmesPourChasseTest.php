<?php
namespace {
    if (!class_exists('WP_Query')) {
        class WP_Query
        {
            public $posts = [];
            public function __construct($args)
            {
                global $last_query_args;
                $last_query_args = $args;
                $this->posts = [];
            }
            public function have_posts()
            {
                return !empty($this->posts);
            }
        }
    }
    if (!function_exists('get_post_type')) {
        function get_post_type($id)
        {
            return 'chasse';
        }
    }
}

namespace RelationsFunctions {
    use PHPUnit\Framework\TestCase;

    class RecupererEnigmesPourChasseTest extends TestCase
    {
        protected function setUp(): void
        {
            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/relations-functions.php';
            global $last_query_args;
            $last_query_args = [];
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_excludes_draft_and_banned(): void
        {
            global $last_query_args;
            \recuperer_enigmes_pour_chasse(10);
            $this->assertSame(['publish', 'pending'], $last_query_args['post_status']);
            $meta = $last_query_args['meta_query'];
            $this->assertSame('enigme_chasse_associee', $meta[0]['key']);
            $this->assertSame('OR', $meta[1]['relation']);
            $this->assertSame('NOT EXISTS', $meta[1][0]['compare']);
            $this->assertSame('banni', $meta[1][1]['value']);
            $this->assertSame('!=', $meta[1][1]['compare']);
        }
    }
}
