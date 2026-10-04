<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleStepProgressRepository;
use PHPUnit\Framework\TestCase;

final class RiddleStepProgressWpdbStub {
    public string $prefix = 'wp_';
    public array $preparedArguments = [];
    public array $completedIds = [];
    public array $insertArguments = [];
    public array $deleteArguments = [];
    public $insertResult = 1;
    public $deleteResult = 1;
    public $status = '0';

    public function prepare(string $query, ...$arguments): string {
        $this->preparedArguments = $arguments;
        return $query;
    }

    public function get_col(string $query): array {
        return $this->completedIds;
    }

    public function get_var(string $query) {
        return $this->status;
    }

    public function insert(string $table, array $data, array $formats) {
        $this->insertArguments = [$table, $data, $formats];
        return $this->insertResult;
    }

    public function delete(string $table, array $where, array $formats) {
        $this->deleteArguments = [$table, $where, $formats];
        return $this->deleteResult;
    }
}

final class RiddleStepProgressRepositoryTest extends TestCase {
    public function testReturnsUniqueCompletedStepIdsForAPlayerAndRiddle(): void {
        $wpdb = new RiddleStepProgressWpdbStub();
        $wpdb->completedIds = ['8', '9', '9', '0'];
        $repository = new RiddleStepProgressRepository($wpdb);

        self::assertSame([8, 9], $repository->findCompletedStepIds(4, 12));
        self::assertSame([4, 12], $wpdb->preparedArguments);
    }

    public function testPersistsACompletedStepWithoutPointsData(): void {
        $wpdb = new RiddleStepProgressWpdbStub();
        $repository = new RiddleStepProgressRepository($wpdb);

        self::assertTrue($repository->markCompleted(4, 12, 8, '2026-10-02 12:00:00', 'attempt-1'));
        self::assertSame('wp_enigme_etapes_progression', $wpdb->insertArguments[0]);
        self::assertSame(8, $wpdb->insertArguments[1]['etape_id']);
        self::assertSame('trouvee', $wpdb->insertArguments[1]['statut']);
        self::assertArrayNotHasKey('points', $wpdb->insertArguments[1]);

        $wpdb->insertResult = false;
        self::assertFalse($repository->markCompleted(4, 12, 8, '2026-10-02 12:00:00', null));
    }

    public function testDetectsAnyProgressForARiddle(): void {
        $wpdb = new RiddleStepProgressWpdbStub();
        $repository = new RiddleStepProgressRepository($wpdb);

        self::assertFalse($repository->hasProgressForRiddle(12));
        $wpdb->status = '1';
        self::assertTrue($repository->hasProgressForRiddle(12));
        self::assertSame([12], $wpdb->preparedArguments);
    }

    public function testCountsDistinctPlayersWithCompletedSteps(): void {
        $wpdb = new RiddleStepProgressWpdbStub();
        $wpdb->status = '3';
        $repository = new RiddleStepProgressRepository($wpdb);

        self::assertSame(3, $repository->countPlayersWithCompletedSteps(12));
        self::assertSame([12], $wpdb->preparedArguments);
        self::assertSame(0, $repository->countPlayersWithCompletedSteps(0));
    }

    public function testDeletesProgressByRiddleOrStep(): void {
        $wpdb = new RiddleStepProgressWpdbStub();
        $repository = new RiddleStepProgressRepository($wpdb);

        self::assertSame(1, $repository->deleteForRiddle(12));
        self::assertSame(['enigme_id' => 12], $wpdb->deleteArguments[1]);
        self::assertSame(1, $repository->deleteForStep(8));
        self::assertSame(['etape_id' => 8], $wpdb->deleteArguments[1]);
    }
}
