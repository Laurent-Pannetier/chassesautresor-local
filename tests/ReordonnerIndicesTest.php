<?php
namespace {
    if (!function_exists('get_posts')) {
        function get_posts($args)
        {
            global $captured_args;
            $captured_args = $args;
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
    if (!function_exists('get_post_type')) {
        function get_post_type($post_id)
        {
            return 'indice';
        }
    }
    if (!function_exists('recuperer_id_chasse_associee')) {
        function recuperer_id_chasse_associee($id)
        {
            global $recuperer_chasse_override;
            return $recuperer_chasse_override[$id] ?? null;
        }
    }
}

namespace ReordonnerIndicesTest {
    use PHPUnit\Framework\TestCase;

    class ReordonnerIndicesTest extends TestCase
    {
        protected function setUp(): void
        {
            global $updated_posts, $simulate_recursion, $get_field_overrides, $captured_args, $recuperer_chasse_override;
            $updated_posts      = [];
            $simulate_recursion = 0;
            $captured_args      = [];
            $get_field_overrides = [
                'indice_cible_type'   => 'chasse',
                'indice_chasse_linked' => 5,
            ];
            $recuperer_chasse_override = [];
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_renames_indices_sequentially(): void
        {
            global $updated_posts, $captured_args;
            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            \reordonner_indices(5, 'chasse');

            $this->assertCount(3, $updated_posts);
            $this->assertSame(['ID' => 10, 'post_title' => 'Indice #1'], $updated_posts[0]);
            $this->assertSame(['ID' => 20, 'post_title' => 'Indice #2'], $updated_posts[1]);
            $this->assertSame(['ID' => 30, 'post_title' => 'Indice #3'], $updated_posts[2]);
            $this->assertSame('indice_chasse_linked', $captured_args['meta_query'][0]['key']);
            $this->assertCount(2, $captured_args['meta_query']);
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_reorders_after_permanent_deletion(): void
        {
            global $updated_posts, $indice_delete_context;
            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            $indice_delete_context = ['chasse_id' => 5];
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
        public function test_reorders_enigme_and_chasse_after_permanent_deletion(): void
        {
            global $updated_posts, $indice_delete_context;
            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            $indice_delete_context = ['chasse_id' => 5, 'enigme_id' => 7];
            \reordonner_indices_apres_suppression(99);

            $this->assertCount(6, $updated_posts);
            $this->assertSame(['ID' => 10, 'post_title' => 'Indice #1'], $updated_posts[0]);
            $this->assertSame(['ID' => 10, 'post_title' => 'Indice #1'], $updated_posts[3]);
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

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_reorders_enigme_indices_using_chasse_context(): void
        {
            global $updated_posts, $get_field_overrides, $captured_args;
            $get_field_overrides['indice_cible_type']   = 'enigme';
            $get_field_overrides['indice_chasse_linked'] = 5;

            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            \reordonner_indices_pour_indice(99);

            $this->assertCount(3, $updated_posts);
            $this->assertSame(['ID' => 10, 'post_title' => 'Indice #1'], $updated_posts[0]);
            $this->assertSame('indice_chasse_linked', $captured_args['meta_query'][0]['key']);
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_reorders_enigme_indices_when_chasse_missing(): void
        {
            global $updated_posts, $get_field_overrides, $recuperer_chasse_override, $captured_args;
            $get_field_overrides['indice_cible_type']    = 'enigme';
            $get_field_overrides['indice_chasse_linked'] = null;
            $get_field_overrides['indice_enigme_linked'] = 7;
            $recuperer_chasse_override                   = [7 => 5];

            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            \reordonner_indices_pour_indice(99);

            $this->assertCount(6, $updated_posts);
            $this->assertSame(['ID' => 10, 'post_title' => 'Indice #1'], $updated_posts[0]);
            $this->assertSame(['ID' => 10, 'post_title' => 'Indice #1'], $updated_posts[3]);
            $this->assertSame(5, $captured_args['meta_query'][0]['value']);
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_memorises_chasse_and_enigme_for_deletion(): void
        {
            global $indice_delete_context, $get_field_overrides, $recuperer_chasse_override;
            $get_field_overrides['indice_cible_type']    = 'enigme';
            $get_field_overrides['indice_chasse_linked'] = null;
            $get_field_overrides['indice_enigme_linked'] = 7;
            $recuperer_chasse_override                   = [7 => 5];

            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            \memoriser_cible_indice_avant_suppression(99);

            $this->assertSame(['chasse_id' => 5, 'enigme_id' => 7], $indice_delete_context);
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_memorises_chasse_from_array_before_deletion(): void
        {
            global $indice_delete_context, $get_field_overrides;
            $get_field_overrides['indice_cible_type']    = 'enigme';
            $get_field_overrides['indice_chasse_linked'] = [['ID' => 5]];
            $get_field_overrides['indice_enigme_linked'] = 7;

            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            \memoriser_cible_indice_avant_suppression(99);

            $this->assertSame(['chasse_id' => 5, 'enigme_id' => 7], $indice_delete_context);
        }
    }
}
