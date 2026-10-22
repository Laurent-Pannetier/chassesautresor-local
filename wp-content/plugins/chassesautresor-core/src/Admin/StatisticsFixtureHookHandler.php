<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Admin;

use ChassesAuTresor\Core\Site\SiteExperienceService;

/** Remove fixture players when demo mode ends, and keep them from signing in. */
final class StatisticsFixtureHookHandler
{
    public static function register(callable $addAction, callable $addFilter): void
    {
        $option = 'update_option_' . SiteExperienceService::OPTION_NAME;
        $addAction($option, [self::class, 'onExperienceUpdated'], 10, 2);
        $addFilter('wp_authenticate_user', [self::class, 'rejectAuthentication']);
        $addFilter('allow_password_reset', [self::class, 'allowPasswordReset'], 10, 2);
        $addAction('pre_get_users', [self::class, 'hideFromUserList']);
    }

    /**
     * @param mixed $old
     * @param mixed $new
     */
    public static function onExperienceUpdated($old, $new): void
    {
        self::purgeIfDemoEnded($old, $new, [StatisticsFixtureGateway::class, 'purge']);
    }

    /**
     * @param mixed $old
     * @param mixed $new
     */
    public static function purgeIfDemoEnded($old, $new, callable $purge): void
    {
        $oldMode = is_array($old) ? (string) ($old['mode'] ?? '') : '';
        $newMode = is_array($new) ? (string) ($new['mode'] ?? '') : '';
        if ($oldMode === SiteExperienceService::MODE_DEMO && $newMode !== $oldMode) {
            $purge();
        }
    }

    /** @param mixed $user */
    public static function rejectAuthentication($user)
    {
        if (!is_object($user) || !isset($user->ID)) {
            return $user;
        }
        if (!StatisticsFixtureService::isFixtureUser((int) $user->ID)) {
            return $user;
        }

        return new \WP_Error(
            'cat_stats_fixture',
            __('Ce compte sert uniquement à prévisualiser les statistiques.', 'chassesautresor-com')
        );
    }

    /** @param mixed $allow */
    public static function allowPasswordReset($allow, $userId)
    {
        if (StatisticsFixtureService::isFixtureUser((int) $userId)) {
            return false;
        }

        return $allow;
    }

    /** @param mixed $query */
    public static function hideFromUserList($query): void
    {
        if (!function_exists('is_admin') || !is_admin() || !is_object($query) || !method_exists($query, 'get')) {
            return;
        }
        if ($query->get('cat_include_fixtures')) {
            return;
        }

        $search = (string) $query->get('search');
        $prefix = StatisticsFixtureService::LOGIN_PREFIX;
        if ($search !== '' && strpos($search, $prefix) !== false) {
            return;
        }

        $exclude = [
            'key' => StatisticsFixtureService::META_KEY,
            'compare' => 'NOT EXISTS',
        ];
        $existing = $query->get('meta_query');
        if (!is_array($existing) || $existing === []) {
            $query->set('meta_query', [$exclude]);
            return;
        }

        $query->set('meta_query', [
            'relation' => 'AND',
            $exclude,
            $existing,
        ]);
    }
}
