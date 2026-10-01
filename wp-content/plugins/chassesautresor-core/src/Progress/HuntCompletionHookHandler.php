<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/**
 * Own the application entry point triggered whenever a riddle is solved.
 */
final class HuntCompletionHookHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('enigme_resolue', [self::class, 'handle'], 10, 2);
    }

    public static function handle(int $userId, int $riddleId): void
    {
        global $wpdb;

        $completion = (new HuntCompletionService(
            CoreServiceFactory::huntProgress($wpdb),
            new HuntRiddleClassifier()
        ))->evaluate($userId, $riddleId);

        if (!$completion['is_automatic'] || !$completion['has_riddles'] || !$completion['is_complete']) {
            return;
        }

        self::completeHunt($completion['hunt_id']);
    }

    public static function completeHunt(int $huntId): void
    {
        if ($huntId <= 0 || get_field('chasse_cache_statut', $huntId) === 'termine') {
            return;
        }

        $riddleIds = self::getRiddleIds($huntId);
        if ($riddleIds === []) {
            return;
        }

        global $wpdb;
        $progress = CoreServiceFactory::huntProgress($wpdb);
        $classified = (new HuntRiddleClassifier())->classify($riddleIds);
        $now = (string) current_time('mysql');
        $winners = $progress->getCompletedUsers(
            $classified['validatable'],
            $classified['engagement_only']
        );
        $maximumWinners = (int) get_field('chasse_infos_nb_max_gagants', $huntId);

        if ($maximumWinners > 0) {
            $winners = array_slice($winners, 0, $maximumWinners);
        }

        $winnerNames = [];
        $winnerRepository = CoreServiceFactory::huntWinners($wpdb);
        foreach ($winners as $winner) {
            $winnerId = (int) $winner->user_id;
            $winnerRepository->save($winnerId, $huntId, (string) ($winner->first_finish ?? $now));
            $user = get_userdata($winnerId);
            if ($user) {
                $winnerNames[] = $user->display_name ?: $user->user_login;
            }
        }

        update_field('chasse_cache_gagnants', implode(', ', $winnerNames), $huntId);
        if ($maximumWinners === 0 || count($winners) >= $maximumWinners) {
            update_field('chasse_cache_date_decouverte', current_time('Y-m-d H:i:s'), $huntId);
            update_field('chasse_cache_complet', 1, $huntId);
            update_field('chasse_cache_statut', 'termine', $huntId);
        }

        foreach ($progress->completeRiddles($riddleIds, $now) as $completedRiddleId => $userIds) {
            foreach ($userIds as $completedUserId) {
                update_user_meta(
                    (int) $completedUserId,
                    'statut_enigme_' . (int) $completedRiddleId,
                    'terminee'
                );
            }
        }

        (new HuntStatusUpdater())->refresh($huntId);
    }

    /** @return int[] */
    private static function getRiddleIds(int $huntId): array
    {
        $values = get_field('chasse_cache_enigmes', $huntId);
        if (!is_array($values)) {
            return [];
        }

        $ids = [];
        foreach ($values as $value) {
            if (is_object($value) && isset($value->ID)) {
                $value = $value->ID;
            } elseif (is_array($value)) {
                $value = reset($value);
            }

            $riddleId = (int) $value;
            if ($riddleId > 0 && get_post_type($riddleId) === 'enigme') {
                $ids[$riddleId] = $riddleId;
            }
        }

        return array_values($ids);
    }
}
