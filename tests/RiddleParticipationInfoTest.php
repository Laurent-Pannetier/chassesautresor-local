<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\PointsService;
use ChassesAuTresor\Core\Progress\RiddleAttemptService;
use ChassesAuTresor\Core\Progress\RiddleParticipationInfoService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/PointsService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAttemptService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleParticipationInfoService.php';

final class ParticipationPointsServiceStub extends PointsService {
    public int $calls = 0;

    public function __construct() {
    }

    public function getBalance(int $userId): int {
        $this->calls++;

        return $userId === 9 ? 80 : 0;
    }
}

final class ParticipationAttemptServiceStub extends RiddleAttemptService {
    public int $calls = 0;

    public function __construct() {
    }

    public function countFailuresTodayForUser(int $userId, int $riddleId, ?DateTimeInterface $now = null): int {
        $this->calls++;

        return $userId === 9 && $riddleId === 12 ? 3 : 0;
    }
}

final class RiddleParticipationInfoTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBuildsParticipationInformationWithoutUnneededLookups(): void {
        $GLOBALS['participation_fields'] = [
            'enigme_mode_validation' => 'automatique',
            'enigme_tentative_cout_points' => 5,
            'enigme_tentative_max' => 10,
        ];
        function get_field(string $field, int $postId) {
            return $GLOBALS['participation_fields'][$field] ?? null;
        }

        $points = new ParticipationPointsServiceStub();
        $attempts = new ParticipationAttemptServiceStub();
        $service = new RiddleParticipationInfoService($points, $attempts);

        self::assertSame([
            'validation_mode' => 'automatique',
            'cost' => 5,
            'balance' => 80,
            'show_attempts' => false,
            'show_info' => true,
            'attempts_used' => 0,
            'attempts_max' => 0,
        ], $service->build(12, 9, false));
        self::assertSame(1, $points->calls);
        self::assertSame(0, $attempts->calls);

        $GLOBALS['participation_fields']['enigme_mode_validation'] = ['value' => 'aucune'];
        self::assertSame([
            'validation_mode' => 'aucune',
            'cost' => 0,
            'balance' => 0,
            'show_attempts' => false,
            'show_info' => false,
            'attempts_used' => 0,
            'attempts_max' => 0,
        ], $service->build(12, 9, false));
        self::assertSame(1, $points->calls);
        self::assertSame(0, $attempts->calls);
    }
}
