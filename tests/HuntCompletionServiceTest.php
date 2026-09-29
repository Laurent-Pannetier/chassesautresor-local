<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntCompletionService;
use ChassesAuTresor\Core\Progress\HuntProgressRepository;
use ChassesAuTresor\Core\Progress\HuntProgressService;
use ChassesAuTresor\Core\Progress\HuntRiddleClassifier;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntProgressRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntProgressService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntRiddleClassifier.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntCompletionService.php';

class HuntCompletionProgressRepository extends HuntProgressRepository
{
    public function __construct()
    {
    }

    public function countSolved(int $userId, array $riddleIds): int
    {
        return count($riddleIds);
    }

    public function countEngaged(int $userId, array $riddleIds): int
    {
        return count($riddleIds);
    }
}

class HuntCompletionClassifier extends HuntRiddleClassifier
{
    protected function getValidationMode(int $riddleId): string
    {
        return $riddleId === 12 ? 'aucune' : 'automatique';
    }
}

class HuntCompletionFixture extends HuntCompletionService
{
    protected function getHuntId(int $riddleId): int
    {
        return 99;
    }

    protected function getEndMode(int $huntId): string
    {
        return 'automatique';
    }

    protected function getRiddleIds(int $huntId): array
    {
        return [10, 11, 12];
    }
}

class HuntCompletionServiceTest extends TestCase
{
    public function testEvaluateReturnsCompleteAutomaticHunt(): void
    {
        $service = new HuntCompletionFixture(
            new HuntProgressService(new HuntCompletionProgressRepository()),
            new HuntCompletionClassifier()
        );

        $this->assertSame(
            ['hunt_id' => 99, 'is_automatic' => true, 'has_riddles' => true, 'is_complete' => true],
            $service->evaluate(7, 12)
        );
    }
}
