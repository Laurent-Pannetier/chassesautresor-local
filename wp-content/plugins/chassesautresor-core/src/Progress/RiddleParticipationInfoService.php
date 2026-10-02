<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Points\PointsService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Build counters and visibility data for a player's riddle participation panel. */
final class RiddleParticipationInfoService {
    private ?PointsService $pointsService;
    private ?RiddleAttemptService $attemptService;

    public function __construct(?PointsService $pointsService = null, ?RiddleAttemptService $attemptService = null) {
        $this->pointsService = $pointsService;
        $this->attemptService = $attemptService;
    }

    /** @return array<string,int|string|bool> */
    public function build(int $riddleId, int $userId, bool $solved): array {
        $mode = $this->validationMode(get_field('enigme_mode_validation', $riddleId));
        $cost = $mode === 'aucune' ? 0 : (int) get_field('enigme_tentative_cout_points', $riddleId);
        $showAttempts = $mode === 'automatique' && !$solved;
        $showInfo = $mode !== 'aucune' && !$solved && ($cost > 0 || $showAttempts);

        return [
            'validation_mode' => $mode,
            'cost' => $cost,
            'balance' => $cost > 0 ? $this->pointsService()->getBalance($userId) : 0,
            'show_attempts' => $showAttempts,
            'show_info' => $showInfo,
            'attempts_used' => $showAttempts
                ? $this->attemptService()->countFailuresTodayForUser($userId, $riddleId)
                : 0,
            'attempts_max' => $showAttempts ? (int) get_field('enigme_tentative_max', $riddleId) : 0,
        ];
    }

    private function pointsService(): PointsService {
        if ($this->pointsService === null) {
            global $wpdb;
            $this->pointsService = CoreServiceFactory::points($wpdb);
        }

        return $this->pointsService;
    }

    private function attemptService(): RiddleAttemptService {
        if ($this->attemptService === null) {
            global $wpdb;
            $this->attemptService = CoreServiceFactory::riddleAttempts($wpdb);
        }

        return $this->attemptService;
    }

    private function validationMode($value): string {
        if (is_array($value)) {
            $value = $value['value'] ?? '';
        }

        $mode = strtolower(trim((string) $value));

        return $mode === '' || strpos($mode, 'aucune') === 0 || $mode === 'none' ? 'aucune' : $mode;
    }
}
