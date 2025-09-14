<?php
namespace {
    if (!defined('TITRE_DEFAUT_INDICE')) {
        define('TITRE_DEFAUT_INDICE', 'indice');
    }
    if (!defined('INDICE_DEFAULT_PREFIX')) {
        define('INDICE_DEFAULT_PREFIX', 'clue-');
    }
    if (!function_exists('get_posts')) {
        function get_posts($args)
        {
            global $captured_args;
            $captured_args[] = $args;
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
    if (!function_exists('update_post_meta')) {
        function update_post_meta($post_id, $key, $value)
        {
            global $updated_meta;
            $updated_meta[$post_id][$key] = $value;
            return true;
        }
    }
    if (!function_exists('get_post_field')) {
        function get_post_field($field, $post_id)
        {
            global $post_titles;
            if ($field === 'post_title') {
                return $post_titles[$post_id] ?? '';
            }
            if ($field === 'post_name') {
                $title = $post_titles[$post_id] ?? '';
                return strtolower(str_replace(' ', '-', $title));
            }
            return '';
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
            global $get_field_overrides;
            return $get_field_overrides[$key] ?? null;
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
            global $updated_posts, $simulate_recursion, $get_field_overrides, $captured_args, $updated_meta, $post_titles;
            $updated_posts      = [];
            $updated_meta       = [];
            $post_titles        = [5 => 'Mocked chasse', 10 => 'Indice #1', 20 => 'Indice #2', 30 => 'Indice #3'];
            $simulate_recursion = 0;
            $captured_args      = [];
            $get_field_overrides = [
                'indice_cible_type'    => 'chasse',
                'indice_chasse_linked' => [['ID' => 5]],
            ];
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_renames_indices_sequentially(): void
        {
            global $updated_posts, $captured_args, $updated_meta;
            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            \reordonner_indices(5, 'chasse');

            $this->assertCount(3, $updated_posts);
            $this->assertSame(['ID' => 10, 'post_title' => 'clue-mocked-chasse'], $updated_posts[0]);
            $this->assertSame(['ID' => 20, 'post_title' => 'clue-mocked-chasse'], $updated_posts[1]);
            $this->assertSame(['ID' => 30, 'post_title' => 'clue-mocked-chasse'], $updated_posts[2]);
            $this->assertSame(1, $updated_meta[10]['indice_rank']);
            $this->assertSame(2, $updated_meta[20]['indice_rank']);
            $this->assertSame(3, $updated_meta[30]['indice_rank']);
            $this->assertSame('indice_chasse_linked', $captured_args[0]['meta_query'][0]['key']);
            $this->assertCount(2, $captured_args[0]['meta_query']);
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_reorders_after_permanent_deletion(): void
        {
            global $updated_posts, $indice_delete_context, $captured_args, $updated_meta;
            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            $indice_delete_context = ['objet_id' => 5, 'objet_type' => 'chasse', 'chasse_id' => 5];
            \reordonner_indices_apres_suppression(99);

            $this->assertCount(3, $updated_posts);
            $this->assertSame(['ID' => 10, 'post_title' => 'clue-mocked-chasse'], $updated_posts[0]);
            $this->assertSame(['ID' => 20, 'post_title' => 'clue-mocked-chasse'], $updated_posts[1]);
            $this->assertSame(['ID' => 30, 'post_title' => 'clue-mocked-chasse'], $updated_posts[2]);
            $this->assertSame(1, $updated_meta[10]['indice_rank']);
            $this->assertSame(2, $updated_meta[20]['indice_rank']);
            $this->assertSame(3, $updated_meta[30]['indice_rank']);
            $this->assertSame('indice_chasse_linked', $captured_args[0]['meta_query'][0]['key']);
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_prevents_recursive_reordering(): void
        {
            global $updated_posts, $simulate_recursion, $updated_meta;
            $simulate_recursion = 1;
            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            \reordonner_indices(5, 'chasse');

            $this->assertCount(3, $updated_posts);
            $this->assertSame(['ID' => 10, 'post_title' => 'clue-mocked-chasse'], $updated_posts[0]);
            $this->assertSame(['ID' => 20, 'post_title' => 'clue-mocked-chasse'], $updated_posts[1]);
            $this->assertSame(['ID' => 30, 'post_title' => 'clue-mocked-chasse'], $updated_posts[2]);
            $this->assertSame(1, $updated_meta[10]['indice_rank']);
            $this->assertSame(2, $updated_meta[20]['indice_rank']);
            $this->assertSame(3, $updated_meta[30]['indice_rank']);
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_reorders_after_save_post(): void
        {
            global $updated_posts, $updated_meta;
            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            \reordonner_indices_apres_enregistrement(99);

            $this->assertCount(3, $updated_posts);
            $this->assertSame(['ID' => 10, 'post_title' => 'clue-mocked-chasse'], $updated_posts[0]);
            $this->assertSame(['ID' => 20, 'post_title' => 'clue-mocked-chasse'], $updated_posts[1]);
            $this->assertSame(['ID' => 30, 'post_title' => 'clue-mocked-chasse'], $updated_posts[2]);
            $this->assertSame(1, $updated_meta[10]['indice_rank']);
            $this->assertSame(2, $updated_meta[20]['indice_rank']);
            $this->assertSame(3, $updated_meta[30]['indice_rank']);
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_reorders_enigme_indices_using_chasse_context(): void
        {
            global $updated_posts, $get_field_overrides, $captured_args, $updated_meta;
            $get_field_overrides['indice_cible_type']   = 'enigme';
            $get_field_overrides['indice_chasse_linked'] = [['ID' => 5]];
            $get_field_overrides['indice_enigme_linked'] = [['ID' => 15]];

            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            \reordonner_indices_pour_indice(99);

            $this->assertCount(6, $updated_posts);
            $this->assertSame(['ID' => 10, 'post_title' => 'clue-mocked-chasse'], $updated_posts[0]);
            $this->assertCount(2, $captured_args);
            $this->assertSame('indice_enigme_linked', $captured_args[0]['meta_query'][1]['key']);
            $this->assertSame('indice_chasse_linked', $captured_args[1]['meta_query'][0]['key']);
            $this->assertSame(1, $updated_meta[10]['indice_rank']);
        }
    }
}
