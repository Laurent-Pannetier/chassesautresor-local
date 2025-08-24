<?php
namespace {
    if (!function_exists('get_posts')) {
        function get_posts($args) {
            global $captured_args;
            $captured_args = $args;
            return [1, 2];
        }
    }
}

namespace ProchainRangIndice {
    use PHPUnit\Framework\TestCase;

    class ProchainRangIndiceTest extends TestCase
    {
        protected function setUp(): void
        {
            global $captured_args;
            $captured_args = [];
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_counts_indices_for_enigme_without_chasse(): void
        {
            global $captured_args;

            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            $rank = \prochain_rang_indice(42, 'enigme');

            $this->assertSame(3, $rank);
            $this->assertSame('indice_enigme_linked', $captured_args['meta_query'][1]['key']);
            $this->assertSame(42, $captured_args['meta_query'][1]['value']);
            $this->assertSame('enigme', $captured_args['meta_query'][0]['value']);
            $this->assertContains('desactive', $captured_args['meta_query'][2]['value']);
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_counts_all_indices_for_chasse(): void
        {
            global $captured_args;

            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            $rank = \prochain_rang_indice(5, 'chasse');

            $this->assertSame(3, $rank);
            $this->assertSame('indice_chasse_linked', $captured_args['meta_query'][0]['key']);
            $this->assertSame(5, $captured_args['meta_query'][0]['value']);
            $this->assertCount(2, $captured_args['meta_query']);
        }
    }
}
