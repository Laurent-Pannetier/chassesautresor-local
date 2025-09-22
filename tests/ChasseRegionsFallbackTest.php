<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('wp_get_post_terms')) {
    function wp_get_post_terms($post_id, $taxonomy, $args = []) {
        return [];
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return false;
    }
}

if (!function_exists('get_field')) {
    function get_field($field, $post_id) {
        global $acf_mocked_fields;

        return $acf_mocked_fields[$post_id][$field] ?? null;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse-functions.php';

class ChasseRegionsFallbackTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_string_field_is_exposed_as_region_badge(): void
    {
        global $acf_mocked_fields;

        $chasse_id = 321;
        $acf_mocked_fields = [
            $chasse_id => [
                'chasse_region' => 'Bretagne',
            ],
        ];

        $expected = [
            [
                'nom'  => 'Bretagne',
                'slug' => 'bretagne',
                'lien' => '',
            ],
        ];

        $this->assertSame($expected, chasse_preparer_termes_affichage($chasse_id, 'chasse_region'));
    }
}

