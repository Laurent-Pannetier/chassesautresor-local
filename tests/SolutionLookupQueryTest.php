<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SolutionLookupQueryTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_query_checks_plain_and_serialized_meta_values(): void
    {
        global $captured_args;
        $captured_args = [];

        if (!function_exists('get_posts')) {
            function get_posts(array $args) {
                global $captured_args;
                $captured_args = $args;
                return [];
            }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/constants.php';
        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse-functions.php';

        solution_recuperer_par_objet(42, 'chasse');

        $meta_query = $captured_args['meta_query'];
        $this->assertSame('AND', $meta_query['relation']);

        $or_group = $meta_query[2] ?? [];
        $this->assertSame('OR', $or_group['relation'] ?? '');

        $values = [];
        foreach ($or_group as $key => $condition) {
            if (is_int($key) && isset($condition['value'])) {
                $values[] = $condition['value'];
            }
        }
        $this->assertContains(42, $values);
        $this->assertContains("\"42\"", $values);
    }
}

