<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

final class HuntModerationService
{
    /** @return array{hunt_status:string,validation_status:string,riddle_status:string,riddle_state:?string}|null */
    public function plan(string $action): ?array
    {
        $plans = [
            'valider' => [
                'hunt_status' => 'publish',
                'validation_status' => 'valide',
                'riddle_status' => 'publish',
                'riddle_state' => null,
            ],
            'correction' => [
                'hunt_status' => 'pending',
                'validation_status' => 'correction',
                'riddle_status' => 'pending',
                'riddle_state' => 'bloquee_chasse',
            ],
            'bannir' => [
                'hunt_status' => 'draft',
                'validation_status' => 'banni',
                'riddle_status' => 'draft',
                'riddle_state' => null,
            ],
            'supprimer' => [
                'hunt_status' => 'trash',
                'validation_status' => 'supprime',
                'riddle_status' => 'trash',
                'riddle_state' => null,
            ],
        ];

        return $plans[$action] ?? null;
    }

    public function requestError(bool $isAdministrator, int $huntId, string $postType, string $action): ?string
    {
        if (!$isAdministrator) {
            return 'access';
        }
        if ($huntId <= 0 || $postType !== 'chasse') {
            return 'hunt';
        }

        return $this->plan($action) === null ? 'action' : null;
    }
}
