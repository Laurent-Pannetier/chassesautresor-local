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

if (!class_exists('WP_Term')) {
    class WP_Term
    {
        public $term_id;
        public $slug = '';
        public $name = '';
        public $taxonomy = '';

        public function __construct($data)
        {
            foreach ((array) $data as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}

if (!function_exists('get_field')) {
    function get_field($field, $post_id) {
        global $acf_mocked_fields;

        return $acf_mocked_fields[$post_id][$field] ?? null;
    }
}

$mocked_terms = [];

if (!function_exists('get_term')) {
    function get_term($term_id, $taxonomy) {
        global $mocked_terms;

        $term_id = (int) $term_id;

        return $mocked_terms[$taxonomy][$term_id] ?? false;
    }
}

if (!function_exists('get_term_by')) {
    function get_term_by($field, $value, $taxonomy) {
        global $mocked_terms;

        foreach ($mocked_terms[$taxonomy] ?? [] as $term) {
            if (!$term instanceof WP_Term) {
                continue;
            }

            if ($field === 'slug' && $term->slug === $value) {
                return $term;
            }

            if ($field === 'name' && $term->name === $value) {
                return $term;
            }
        }

        return false;
    }
}

if (!function_exists('get_term_link')) {
    function get_term_link($term) {
        if ($term instanceof WP_Term) {
            return 'https://example.com/' . $term->taxonomy . '/' . $term->slug;
        }

        return '';
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

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_term_identifier_string_is_resolved(): void
    {
        global $acf_mocked_fields, $mocked_terms;

        $chasse_id = 654;
        $mocked_terms = [
            'chasse_region' => [
                9 => new WP_Term((object) [
                    'term_id'  => 9,
                    'slug'     => 'occitanie',
                    'name'     => 'Occitanie',
                    'taxonomy' => 'chasse_region',
                ]),
            ],
        ];

        $acf_mocked_fields = [
            $chasse_id => [
                'chasse_region' => 'term_9',
            ],
        ];

        $expected = [
            [
                'nom'  => 'Occitanie',
                'slug' => 'occitanie',
                'lien' => 'https://example.com/chasse_region/occitanie',
            ],
        ];

        $this->assertSame($expected, chasse_preparer_termes_affichage($chasse_id, 'chasse_region'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_array_value_with_term_identifier_is_resolved(): void
    {
        global $acf_mocked_fields, $mocked_terms;

        $chasse_id = 987;
        $mocked_terms = [
            'chasse_region' => [
                77 => new WP_Term((object) [
                    'term_id'  => 77,
                    'slug'     => 'ile-de-france',
                    'name'     => 'Île-de-France',
                    'taxonomy' => 'chasse_region',
                ]),
            ],
        ];

        $acf_mocked_fields = [
            $chasse_id => [
                'chasse_region' => [
                    'label' => 'Île-de-France',
                    'value' => 'term:77',
                ],
            ],
        ];

        $expected = [
            [
                'nom'  => 'Île-de-France',
                'slug' => 'ile-de-france',
                'lien' => 'https://example.com/chasse_region/ile-de-france',
            ],
        ];

        $this->assertSame($expected, chasse_preparer_termes_affichage($chasse_id, 'chasse_region'));
    }
}

