<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

use ChassesAuTresor\Core\Notifications\NotificationService;
use ChassesAuTresor\Core\Teams\TeamStore;

/** Attribute one trophy once per recipient and publish its user notification. */
final class TrophyAwardService
{
    public function __construct(
        private readonly TrophyAwardStore $awards,
        private readonly NotificationService $notifications,
        private readonly TeamStore $teams
    ) {
    }

    /**
     * @param array<string, mixed> $context
     * @return int[] Newly-created award identifiers.
     */
    public function award(
        TrophyDefinition $trophy,
        int $userId,
        string $eventKey,
        array $context = []
    ): array {
        if (!$trophy->active || $userId <= 0 || trim($eventKey) === '') {
            return [];
        }
        $context['trophy_snapshot'] = [
            'title' => $trophy->title,
            'description' => $trophy->description,
            'image_id' => $trophy->imageId,
        ];

        return $this->awards->transaction(function () use ($trophy, $userId, $eventKey, $context): array {
            $awardIds = [];
            $notificationUsers = [];
            if (in_array($trophy->scope, ['user', 'both'], true)) {
                $awardId = $this->awards->insert(
                    $trophy->id,
                    'user',
                    $userId,
                    $userId,
                    $eventKey,
                    $context
                );
                if ($awardId > 0) {
                    $awardIds[] = $awardId;
                    $notificationUsers[] = $userId;
                }
            }

            $teamId = in_array($trophy->scope, ['team', 'both'], true)
                ? $this->teams->activeTeamId($userId)
                : 0;
            if ($teamId > 0) {
                $awardId = $this->awards->insert(
                    $trophy->id,
                    'team',
                    $teamId,
                    $userId,
                    $eventKey,
                    $context
                );
                if ($awardId > 0) {
                    $awardIds[] = $awardId;
                    foreach ($this->teams->activeMembers($teamId) as $member) {
                        $notificationUsers[] = (int) ($member['user_id'] ?? 0);
                    }
                }
            }

            if ($awardIds !== [] && $trophy->notificationEnabled) {
                $this->notify($trophy, $awardIds, $notificationUsers);
            }

            return $awardIds;
        });
    }

    /**
     * @param int[] $awardIds
     * @param int[] $userIds
     */
    private function notify(TrophyDefinition $trophy, array $awardIds, array $userIds): void
    {
        $title = trim($trophy->notificationTitle);
        if ($title === '') {
            $title = sprintf(
                __('Nouveau trophée : %s', 'chassesautresor-com'),
                $trophy->title
            );
        }
        $body = trim($trophy->notificationBody);
        if ($body === '') {
            $body = $trophy->description;
        }
        sort($awardIds);
        $this->notifications->notify(
            'trophy_awarded',
            'trophy',
            $trophy->id,
            $title,
            $body,
            $trophy->notificationPriority,
            $trophy->notificationChannel,
            'trophy_awards:' . implode('-', $awardIds),
            $userIds
        );
    }
}
