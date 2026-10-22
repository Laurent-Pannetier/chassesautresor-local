<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Admin;

use ChassesAuTresor\Core\Content\RiddleStepQueryService;
use ChassesAuTresor\Core\Progress\StatisticsCacheService;
use DateTimeImmutable;
use DateTimeZone;

/** Connect the statistics fixture service to WordPress users and the hunt catalog. */
final class StatisticsFixtureGateway
{
    public static function generate(int $huntId): array
    {
        if (function_exists('set_time_limit')) {
            set_time_limit(120);
        }

        self::silenceNotifications();
        try {
            return self::service(static function (int $number) use ($huntId): int {
                return self::createUser($number, $huntId);
            })->generate($huntId, self::catalog($huntId));
        } finally {
            self::restoreNotifications();
        }
    }

    /** @return array{users:int,rows:int,error:string} */
    public static function purge(): array
    {
        return self::service(static function (): int {
            return 0;
        })->purge();
    }

    /** @return int[] */
    public static function findUserIds(): array
    {
        global $wpdb;

        if (!isset($wpdb->usermeta)) {
            return [];
        }

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
            StatisticsFixtureService::META_KEY,
            '1'
        ));

        return array_values(array_unique(array_map('intval', (array) $ids)));
    }

    /** @return int[] */
    public static function findHuntIds(): array
    {
        global $wpdb;

        if (!isset($wpdb->usermeta)) {
            return [];
        }

        $values = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value <> ''",
            StatisticsFixtureService::HUNT_META
        ));

        return array_values(array_filter(array_map('intval', (array) $values)));
    }

    public static function createUser(int $number, int $huntId): int
    {
        if ($number <= 0 || $huntId <= 0 || !function_exists('wp_insert_user')) {
            return 0;
        }

        $login = StatisticsFixtureService::LOGIN_PREFIX . $number;
        $existingId = self::existingFixtureId($login);
        if ($existingId > 0) {
            self::mark($existingId, $huntId);
            return $existingId;
        }

        $password = function_exists('wp_generate_password')
            ? wp_generate_password(64, true, true)
            : bin2hex(random_bytes(32));
        $userId = wp_insert_user([
            'user_login' => $login,
            'user_pass' => $password,
            'user_email' => $login . '@fixture.invalid',
            'display_name' => sprintf(
                /* translators: %d: fixture player number. */
                __('Joueur test %d', 'chassesautresor-com'),
                $number
            ),
            'role' => 'subscriber',
            'show_admin_bar_front' => 'false',
        ]);
        if ((function_exists('is_wp_error') && is_wp_error($userId)) || (int) $userId <= 0) {
            return 0;
        }

        self::mark((int) $userId, $huntId);

        return (int) $userId;
    }

    public static function deleteUser(int $userId): bool
    {
        if (!StatisticsFixtureService::isFixtureUser($userId)) {
            return false;
        }
        if (!function_exists('wp_delete_user')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        return wp_delete_user($userId) === true;
    }

    /** @param int[] $riddleIds */
    public static function clearCache(int $huntId, array $riddleIds): void
    {
        if ($huntId <= 0) {
            return;
        }
        if ($riddleIds === [] && function_exists('recuperer_ids_enigmes_pour_chasse')) {
            $riddleIds = recuperer_ids_enigmes_pour_chasse($huntId);
        }

        $cache = new StatisticsCacheService();
        $cache->clear('chasse', $huntId);
        foreach ($riddleIds as $riddleId) {
            $cache->clear('enigme', (int) $riddleId);
        }
        AdminStatisticsResetService::clearHuntDisplayCache($huntId);
    }

    /** @return array{hunt_hints:int[],riddles:array<int,array<string,mixed>>} */
    public static function catalog(int $huntId): array
    {
        $riddleIds = function_exists('recuperer_ids_enigmes_pour_chasse')
            ? recuperer_ids_enigmes_pour_chasse($huntId)
            : [];
        $steps = new RiddleStepQueryService();
        $riddles = [];
        foreach ((array) $riddleIds as $riddleId) {
            $riddleId = (int) $riddleId;
            if ($riddleId <= 0) {
                continue;
            }
            $mode = function_exists('get_field') ? (string) get_field('enigme_mode_validation', $riddleId) : '';
            $stepRows = [];
            foreach ($steps->findOrderedIds($riddleId) as $stepId) {
                $stepRows[] = ['id' => (int) $stepId];
            }
            $riddles[] = [
                'id' => $riddleId,
                'validation' => $mode,
                'steps' => $stepRows,
                'hints' => self::hintIds('enigme', 'indice_enigme_linked', $riddleId),
            ];
        }

        return [
            'hunt_hints' => self::hintIds('chasse', 'indice_chasse_linked', $huntId),
            'riddles' => $riddles,
        ];
    }

    private static function service(callable $createUser): StatisticsFixtureService
    {
        global $wpdb;

        return new StatisticsFixtureService(
            $wpdb,
            [self::class, 'findUserIds'],
            $createUser,
            [self::class, 'deleteUser'],
            [self::class, 'findHuntIds'],
            static function (): DateTimeImmutable {
                return new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
            },
            [self::class, 'clearCache']
        );
    }

    private static function mark(int $userId, int $huntId): void
    {
        update_user_meta($userId, StatisticsFixtureService::META_KEY, '1');
        update_user_meta($userId, StatisticsFixtureService::HUNT_META, (string) $huntId);
    }

    private static function existingFixtureId(string $login): int
    {
        if (!function_exists('get_user_by')) {
            return 0;
        }

        $user = get_user_by('login', $login);
        if (!is_object($user) || !isset($user->ID)) {
            return 0;
        }

        $userId = (int) $user->ID;

        return StatisticsFixtureService::isFixtureUser($userId) ? $userId : 0;
    }

    /** @return int[] */
    private static function hintIds(string $targetType, string $relationKey, int $targetId): array
    {
        if ($targetId <= 0 || !function_exists('get_posts')) {
            return [];
        }

        $posts = get_posts([
            'post_type' => 'indice',
            'post_status' => ['publish', 'draft', 'future', 'pending'],
            'fields' => 'ids',
            'meta_query' => [
                ['key' => 'indice_cible_type', 'value' => $targetType, 'compare' => '='],
                ['key' => $relationKey, 'value' => (string) $targetId, 'compare' => '='],
                [
                    'key' => 'indice_cache_etat_systeme',
                    'value' => ['accessible', 'programme'],
                    'compare' => 'IN',
                ],
            ],
            'orderby' => 'date',
            'order' => 'ASC',
            'no_found_rows' => true,
            'posts_per_page' => -1,
        ]);

        return array_values(array_filter(array_map('intval', (array) $posts)));
    }

    private static function silenceNotifications(): void
    {
        if (!function_exists('add_filter')) {
            return;
        }
        add_filter('wp_send_new_user_notification_to_user', '__return_false', 99);
        add_filter('wp_send_new_user_notification_to_admin', '__return_false', 99);
    }

    private static function restoreNotifications(): void
    {
        if (!function_exists('remove_filter')) {
            return;
        }
        remove_filter('wp_send_new_user_notification_to_user', '__return_false', 99);
        remove_filter('wp_send_new_user_notification_to_admin', '__return_false', 99);
    }
}
