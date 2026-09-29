<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntProgressRepository;
use ChassesAuTresor\Core\Progress\HuntProgressService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntProgressRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntProgressService.php';

class HuntProgressRepositoryStub extends HuntProgressRepository
{
    public function __construct()
    {
    }

    public function countSolved(int $userId, array $riddleIds): int
    {
        return 2;
    }

    public function countEngaged(int $userId, array $riddleIds): int
    {
        return 1;
    }

    public function findCompletedUsers(array $validatable, array $engagementOnly): array
    {
        return [(object) ['user_id' => 7, 'first_finish' => '2026-09-29 10:00:00']];
    }
}

class HuntProgressServiceTest extends TestCase
{
    public function testCalculateCombinesSolvedAndEngagedRiddles(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());

        $this->assertSame(
            ['completed' => 3, 'total' => 3, 'is_complete' => true],
            $service->calculate(7, [10, 11], [12])
        );
    }

    public function testCalculateRejectsInvalidUser(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());

        $this->assertSame(
            ['completed' => 0, 'total' => 0, 'is_complete' => false],
            $service->calculate(0, [10], [])
        );
    }

    public function testCompletedUsersComeFromProgressRepository(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());
        $users = $service->getCompletedUsers([10, 11], [12]);

        $this->assertSame(7, $users[0]->user_id);
        $this->assertSame('2026-09-29 10:00:00', $users[0]->first_finish);
    }
}
