<?php

declare(strict_types=1);

use PHPUnit\\Framework\\TestCase;

/**
 * Test doubles for WordPress helpers used by the homepage filter logic.
 *
 * - WP_Query exposes the hunt identifiers injected in $mocked_wp_query_posts.
 * - wp_reset_postdata() is a no-op because no global loop state exists in tests.
 * - get_current_user_id() returns a deterministic identifier for cache keys.
 * - add_action() records callbacks without touching the global hooks registry.
 * - chasse_est_visible_pour_utilisateur() always returns true during tests.
 * - preparer_infos_affichage_chasse() returns fixtures stored in $mocked_chasse_infos.
 * - get_field() proxies selected ACF fields when helpers fall back to it.
 * - get_the_title() returns values stored in $mocked_post_titles.
 * - get_post_field() exposes excerpts defined in $mocked_post_fields.
 * - wp_get_post_terms() retrieves taxonomy fixtures from $mocked_taxonomy_terms.
 */
if (!class_exists('WP_Query')) {
    class WP_Query
    {
        /**
         * @var array<int, int>
         */
        public array $posts = [];

        /**
         * @param array<string, mixed> $args
         */
        public function __construct(array $args = [])
        {
            global $mocked_wp_query_posts;
            $this->posts = array_map('intval', $mocked_wp_query_posts ?? []);
        }
    }
}

if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata(): void
    {
        // Intentionally left blank.
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id(): int
    {
        return 42;
    }
}

if (!function_exists('add_action')) {
    function add_action(string $hook, callable $callback): void
    {
        global $registered_actions;
        $registered_actions[$hook][] = $callback;
    }
}

if (!function_exists('chasse_est_visible_pour_utilisateur')) {
    function chasse_est_visible_pour_utilisateur(int $chasse_id, int $user_id): bool
    {
        return true;
    }
}

if (!function_exists('preparer_infos_affichage_chasse')) {
    /**
     * @return array<string, mixed>
     */
    function preparer_infos_affichage_chasse(int $chasse_id, int $user_id): array
    {
        global $mocked_chasse_infos;
        return $mocked_chasse_infos[$chasse_id] ?? [];
    }
}

if (!function_exists('get_field')) {
    /**
     * @return mixed
     */
    function get_field(string $key, int $post_id)
    {
        global $mocked_chasse_infos;

        if ('chasse_cache_statut' === $key) {
            return $mocked_chasse_infos[$post_id]['statut'] ?? null;
        }

        if ('chasse_infos_cout_points' === $key) {
            return $mocked_chasse_infos[$post_id]['champs']['cout_points'] ?? null;
        }

        return null;
    }
}

class HomepageFiltersTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_filters_by_status_and_cost(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }

        $this->ensureHomepageFilterStubs();

        global $mocked_wp_query_posts, $mocked_chasse_infos, $mocked_post_titles, $mocked_post_fields, $mocked_taxonomy_terms;

        $mocked_wp_query_posts = [101, 102, 103, 104];
        $mocked_chasse_infos = [
            101 => [
                'statut' => 'en_cours',
                'champs' => [
                    'cout_points' => 0,
                ],
                'nb_enigmes_payantes' => 0,
            ],
            102 => [
                'statut' => 'payante',
                'champs' => [
                    'cout_points' => 50,
                ],
                'nb_enigmes_payantes' => 0,
            ],
            103 => [
                'statut' => 'termine',
                'champs' => [
                    'cout_points' => 0,
                ],
                'nb_enigmes_payantes' => 0,
            ],
            104 => [
                'statut' => 'en_cours',
                'champs' => [
                    'cout_points' => 0,
                ],
                'nb_enigmes_payantes' => 1,
            ],
        ];

        $mocked_post_titles      = [];
        $mocked_post_fields      = [];
        $mocked_taxonomy_terms   = [];

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/homepage-filters.php';

        $statusResults = ca_home_filter_chasse_ids([
            'statut' => 'en_cours',
        ]);

        $this->assertSame([101, 102, 104], $statusResults['ids']);
        $this->assertSame(3, $statusResults['total']);
        $this->assertSame('en_cours', $statusResults['filters_normalises']['statut']);
        $this->assertSame(['gratuit', 'points'], $statusResults['filters_normalises']['cout']);
        $this->assertSame(
            [
                'tous'     => 4,
                'en_cours' => 3,
                'a_venir'  => 0,
                'termine'  => 1,
            ],
            $statusResults['available_filters']['statut']
        );
        $this->assertSame(
            [
                'gratuit' => 2,
                'points'  => 2,
            ],
            $statusResults['available_filters']['cout']
        );

        $freeResults = ca_home_filter_chasse_ids([
            'cout' => 'gratuit',
        ]);

        $this->assertSame([101, 103], $freeResults['ids']);
        $this->assertSame(2, $freeResults['total']);
        $this->assertSame('tous', $freeResults['filters_normalises']['statut']);
        $this->assertSame(['gratuit'], $freeResults['filters_normalises']['cout']);
        $this->assertSame(
            [
                'tous'     => 4,
                'en_cours' => 3,
                'a_venir'  => 0,
                'termine'  => 1,
            ],
            $freeResults['available_filters']['statut']
        );
        $this->assertSame(
            [
                'gratuit' => 2,
                'points'  => 2,
            ],
            $freeResults['available_filters']['cout']
        );

        $pointsResults = ca_home_filter_chasse_ids([
            'cout' => ['points'],
        ]);

        $this->assertSame([102, 104], $pointsResults['ids']);
        $this->assertSame(2, $pointsResults['total']);
        $this->assertSame('tous', $pointsResults['filters_normalises']['statut']);
        $this->assertSame(['points'], $pointsResults['filters_normalises']['cout']);
        $this->assertSame(
            [
                'tous'     => 4,
                'en_cours' => 3,
                'a_venir'  => 0,
                'termine'  => 1,
            ],
            $pointsResults['available_filters']['statut']
        );
        $this->assertSame(
            [
                'gratuit' => 2,
                'points'  => 2,
            ],
            $pointsResults['available_filters']['cout']
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_search_matches_taxonomy_terms(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }

        $this->ensureHomepageFilterStubs();

        global $mocked_wp_query_posts, $mocked_chasse_infos, $mocked_taxonomy_terms, $mocked_post_titles, $mocked_post_fields;

        $mocked_wp_query_posts = [201, 202, 203];
        $mocked_chasse_infos = [
            201 => [
                'statut' => 'en_cours',
                'champs' => [
                    'cout_points' => 0,
                ],
                'nb_enigmes_payantes' => 0,
            ],
            202 => [
                'statut' => 'en_cours',
                'champs' => [
                    'cout_points' => 0,
                ],
                'nb_enigmes_payantes' => 0,
            ],
            203 => [
                'statut' => 'en_cours',
                'champs' => [
                    'cout_points' => 0,
                ],
                'nb_enigmes_payantes' => 0,
            ],
        ];

        $mocked_taxonomy_terms = [
            201 => [
                'chasse_region' => [
                    ['nom' => 'PACA'],
                ],
                'theme_chasse' => [
                    ['nom' => 'Aventure'],
                ],
            ],
            202 => [
                'chasse_region' => [
                    ['nom' => 'Bretagne'],
                ],
                'theme_chasse' => [
                    ['nom' => 'Patrimoine'],
                ],
            ],
            203 => [
                'chasse_region' => [
                    ['nom' => 'Île-de-France'],
                ],
                'theme_chasse' => [
                    ['nom' => 'Gastronomie'],
                ],
            ],
        ];

        $mocked_post_titles = [
            201 => 'Chasse Provence',
            202 => 'Chasse Bretagne',
            203 => 'Chasse Paris',
        ];

        $mocked_post_fields = [
            201 => ['post_excerpt' => ''],
            202 => ['post_excerpt' => ''],
            203 => ['post_excerpt' => ''],
        ];

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/homepage-filters.php';

        $regionResults = ca_home_filter_chasse_ids([
            'search' => 'bretagne',
        ]);

        $this->assertSame([202], $regionResults['ids']);
        $this->assertSame(1, $regionResults['total']);

        $themeResults = ca_home_filter_chasse_ids([
            'search' => 'aventure',
        ]);

        $this->assertSame([201], $themeResults['ids']);
        $this->assertSame(1, $themeResults['total']);
    }

    private function ensureHomepageFilterStubs(): void
    {
        if (!function_exists('get_the_title')) {
            function get_the_title($post = 0)
            {
                global $mocked_post_titles;

                $post_id = 0;

                if (is_object($post) && isset($post->ID)) {
                    $post_id = (int) $post->ID;
                } elseif (is_numeric($post)) {
                    $post_id = (int) $post;
                }

                return $mocked_post_titles[$post_id] ?? 'Post ' . $post_id;
            }
        }

        if (!function_exists('get_post_field')) {
            function get_post_field($field, $post_id, $context = 'display')
            {
                global $mocked_post_fields;

                if ('post_excerpt' === $field) {
                    return $mocked_post_fields[$post_id]['post_excerpt'] ?? '';
                }

                return $mocked_post_fields[$post_id][$field] ?? '';
            }
        }

        if (!function_exists('wp_get_post_terms')) {
            function wp_get_post_terms($post_id, $taxonomy, $args = [])
            {
                global $mocked_taxonomy_terms;

                if (!isset($mocked_taxonomy_terms[$post_id][$taxonomy])) {
                    return [];
                }

                return $mocked_taxonomy_terms[$post_id][$taxonomy];
            }
        }
    }
}
