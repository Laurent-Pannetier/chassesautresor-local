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

        global $mocked_wp_query_posts, $mocked_chasse_infos;

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
}
