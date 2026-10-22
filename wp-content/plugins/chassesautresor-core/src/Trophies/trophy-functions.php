<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Notifications\NotificationService;
use ChassesAuTresor\Core\Teams\TeamRepository;
use ChassesAuTresor\Core\Trophies\TrophyAwardRepository;
use ChassesAuTresor\Core\Trophies\TrophyAwardService;
use ChassesAuTresor\Core\Trophies\TrophyDefinitionRepository;
use ChassesAuTresor\Core\Trophies\TrophyEvent;
use ChassesAuTresor\Core\Trophies\TrophyEventDispatcher;
use ChassesAuTresor\Core\Trophies\TrophyTriggerRegistry;

if (!function_exists('cat_trophy_award_repository')) {
    function cat_trophy_award_repository(): TrophyAwardRepository
    {
        global $wpdb;

        return new TrophyAwardRepository($wpdb);
    }
}

if (!function_exists('cat_trophy_team_repository')) {
    function cat_trophy_team_repository(): TeamRepository
    {
        global $wpdb;

        return new TeamRepository($wpdb);
    }
}

if (!function_exists('cat_trophy_award_service')) {
    function cat_trophy_award_service(): TrophyAwardService
    {
        $notifications = function_exists('cat_notification_service')
            ? cat_notification_service()
            : new NotificationService(cat_notification_repository());

        return new TrophyAwardService(
            cat_trophy_award_repository(),
            $notifications,
            cat_trophy_team_repository()
        );
    }
}

if (!function_exists('cat_dispatch_trophy_event')) {
    /**
     * Public extension point used by future application code.
     *
     * @param array<string, mixed> $context
     * @return array<int, int[]>
     */
    function cat_dispatch_trophy_event(string $eventKey, int $userId, array $context = []): array
    {
        $dispatcher = new TrophyEventDispatcher(
            new TrophyDefinitionRepository(),
            new TrophyTriggerRegistry(),
            cat_trophy_award_service()
        );

        return $dispatcher->dispatch(new TrophyEvent('code_event', $userId, $eventKey, $context));
    }
}

if (!function_exists('cat_user_trophy_count')) {
    function cat_user_trophy_count(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $teamId = cat_trophy_team_repository()->activeTeamId($userId);

        return cat_trophy_award_repository()->countForRecipients($userId, $teamId);
    }
}

if (!function_exists('cat_user_trophies')) {
    /** @return array<int, array<string, mixed>> */
    function cat_user_trophies(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        $teamId = cat_trophy_team_repository()->activeTeamId($userId);
        $rows = cat_trophy_award_repository()->listForRecipients($userId, $teamId);
        $items = [];
        foreach ($rows as $row) {
            $trophyId = (int) ($row['trophy_id'] ?? 0);
            if ($trophyId <= 0) {
                continue;
            }
            if (!isset($items[$trophyId])) {
                $context = json_decode((string) ($row['context'] ?? ''), true);
                $snapshot = is_array($context['trophy_snapshot'] ?? null)
                    ? $context['trophy_snapshot']
                    : [];
                $imageId = (int) ($snapshot['image_id'] ?? get_post_thumbnail_id($trophyId));
                $image = function_exists('wp_get_attachment_image_url')
                    ? wp_get_attachment_image_url($imageId, 'medium')
                    : '';
                $items[$trophyId] = [
                    'id' => $trophyId,
                    'title' => (string) ($snapshot['title'] ?? get_the_title($trophyId)),
                    'description' => (string) (
                        $snapshot['description'] ?? get_post_field('post_excerpt', $trophyId)
                    ),
                    'image_url' => is_string($image) ? $image : '',
                    'awarded_at' => (string) ($row['awarded_at'] ?? ''),
                    'recipient_types' => [],
                ];
            }
            $items[$trophyId]['recipient_types'][] = (string) ($row['recipient_type'] ?? 'user');
            $items[$trophyId]['recipient_types'] = array_values(array_unique(
                $items[$trophyId]['recipient_types']
            ));
        }

        return array_values($items);
    }
}
