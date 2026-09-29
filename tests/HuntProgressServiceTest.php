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
    public array $completedRiddles = [];
    public array $statusArguments = [];
    public array $persistedStatus = [];
    public ?string $status = 'resolue';
    public array $statuses = [];
    public int $deletedStatuses = 0;

    public function __construct()
    {
    }

    public function countSolved(int $userId, array $riddleIds): int
    {
        return 2;
    }

    public function findStatus(int $userId, int $riddleId): ?string
    {
        $this->statusArguments = [$userId, $riddleId];

        return $this->statuses[$riddleId] ?? $this->status;
    }

    public function persistStatus(
        int $userId,
        int $riddleId,
        string $status,
        string $updatedAt,
        bool $statusExists
    ): void {
        $this->persistedStatus = [$userId, $riddleId, $status, $updatedAt, $statusExists];
    }

    public function findResolutionDate(int $userId, int $riddleId): ?string
    {
        $this->statusArguments = [$userId, $riddleId];

        return '2026-09-29 12:00:00';
    }

    public function deleteStatusesForRiddle(int $riddleId): int
    {
        $this->statusArguments = [$riddleId];
        return $this->deletedStatuses;
    }

    public function countEngaged(int $userId, array $riddleIds): int
    {
        return 1;
    }

    public function countValidatable(array $riddleIds): int
    {
        return 2;
    }

    public function findCompletedUsers(array $validatable, array $engagementOnly): array
    {
        return [(object) ['user_id' => 7, 'first_finish' => '2026-09-29 10:00:00']];
    }

    public function completeRiddles(array $riddleIds, string $completedAt): array
    {
        $this->completedRiddles = [$riddleIds, $completedAt];

        return [10 => [7, 8]];
    }
}

class HuntProgressServiceTest extends TestCase
{
    public function testRepositoryCompletesRiddlesAndReturnsAffectedUsers(): void
    {
        $wpdb = new class {
            public string $prefix = 'wp_';
            public array $updates = [];

            public function update($table, $data, $where, $format, $whereFormat): void
            {
                $this->updates[] = compact('table', 'data', 'where', 'format', 'whereFormat');
            }

            public function prepare($query, ...$args): string
            {
                return $query . ':' . implode(',', $args);
            }

            public function get_col($query): array
            {
                return ['7', '8'];
            }
        };
        $repository = new HuntProgressRepository($wpdb);

        $this->assertSame(
            [10 => [7, 8]],
            $repository->completeRiddles([10], '2026-09-29 11:00:00')
        );
        $this->assertSame('wp_enigme_statuts_utilisateur', $wpdb->updates[0]['table']);
        $this->assertSame(['enigme_id' => 10], $wpdb->updates[0]['where']);
        $this->assertSame('terminee', $wpdb->updates[0]['data']['statut']);
    }

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

    public function testRiddleStatusIsDelegatedForValidIdentifiers(): void
    {
        $repository = new HuntProgressRepositoryStub();
        $service = new HuntProgressService($repository);

        $this->assertSame('resolue', $service->getRiddleStatus(7, 10));
        $this->assertSame([7, 10], $repository->statusArguments);
        $this->assertNull($service->getRiddleStatus(0, 10));
        $this->assertNull($service->getRiddleStatus(7, 0));
    }

    public function testRiddleResolutionDateIsDelegatedForValidIdentifiers(): void
    {
        $repository = new HuntProgressRepositoryStub();
        $service = new HuntProgressService($repository);

        $this->assertSame('2026-09-29 12:00:00', $service->getRiddleResolutionDate(7, 10));
        $this->assertSame([7, 10], $repository->statusArguments);
        $this->assertNull($service->getRiddleResolutionDate(0, 10));
    }

    public function testRiddlePrerequisitesRequireEveryRiddleToBeCompleted(): void
    {
        $repository = new HuntProgressRepositoryStub();
        $repository->statuses = [10 => 'resolue', 11 => 'terminee'];
        $service = new HuntProgressService($repository);

        $this->assertTrue($service->areRiddlePrerequisitesMet(7, [10, 11]));

        $repository->statuses[11] = 'en_cours';
        $this->assertFalse($service->areRiddlePrerequisitesMet(7, [10, 11]));
        $this->assertFalse($service->areRiddlePrerequisitesMet(0, [10]));
    }

    public function testRiddlePrerequisitesApplyEmptyConfigurationRule(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());

        $this->assertTrue($service->areRiddlePrerequisitesMet(7, []));
        $this->assertTrue($service->areRiddlePrerequisitesMet(7, [0, -1]));
        $this->assertFalse($service->areRiddlePrerequisitesMet(7, [], 'pre_requis'));
        $this->assertFalse($service->areRiddlePrerequisitesMet(7, [0, -1], 'pre_requis'));
    }

    public function testRiddleLockStateMapsBusinessStatuses(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());

        $this->assertSame('utilisateur_non_connecte', $service->getRiddleLockState(0, 'en_cours')['motif']);
        $this->assertSame('pre_requis', $service->getRiddleLockState(7, 'bloquee_pre_requis')['motif']);
        $this->assertSame('chasse_indisponible', $service->getRiddleLockState(7, 'bloquee_chasse')['motif']);
        $this->assertSame('non_souscrit', $service->getRiddleLockState(7, 'non_souscrite')['motif']);
        $this->assertSame('erreur_configuration', $service->getRiddleLockState(7, 'invalide')['motif']);
        $this->assertFalse($service->getRiddleLockState(7, 'en_cours')['est_verrouillee']);
    }

    public function testRiddleDateLockIncludesRemainingTime(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());
        $state = $service->getRiddleLockState(7, 'bloquee_date', 2000, 'date', 1500);

        $this->assertTrue($state['est_verrouillee']);
        $this->assertSame('date_future', $state['motif']);
        $this->assertSame('date', $state['date_deblocage']);
        $this->assertSame(500, $state['timestamp_restant']);
        $this->assertSame(
            'date_non_definie',
            $service->getRiddleLockState(7, 'bloquee_date', 1000, 'date', 1500)['motif']
        );
    }

    public function testRiddleParticipationStateMapsRedirectsAndForms(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());

        foreach (['abandonnee', 'bloquee_date', 'bloquee_chasse', 'bloquee_pre_requis', 'invalide'] as $status) {
            $state = $service->getRiddleParticipationState($status);
            $this->assertTrue($state['rediriger']);
            $this->assertFalse($state['afficher_formulaire']);
        }

        foreach (['en_cours', 'non_souscrite', 'echouee'] as $status) {
            $state = $service->getRiddleParticipationState($status);
            $this->assertFalse($state['rediriger']);
            $this->assertTrue($state['afficher_formulaire']);
        }

        $submitted = $service->getRiddleParticipationState('soumis');
        $this->assertFalse($submitted['rediriger']);
        $this->assertFalse($submitted['afficher_formulaire']);
    }

    public function testRiddleSystemStatePrioritizesHuntAndAccessRules(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());

        $this->assertSame(
            'bloquee_chasse',
            $service->calculateRiddleSystemState(false, '', 'immediat', null, 'manuelle', false, 1000)
        );
        $this->assertSame(
            'bloquee_chasse',
            $service->calculateRiddleSystemState(true, 'a_venir', 'immediat', null, 'manuelle', false, 1000)
        );
        $this->assertSame(
            'bloquee_date',
            $service->calculateRiddleSystemState(true, 'en_cours', 'date_programmee', 2000, 'manuelle', false, 1000)
        );
        $this->assertSame(
            'bloquee_pre_requis',
            $service->calculateRiddleSystemState(true, 'payante', 'pre_requis', null, 'manuelle', false, 1000)
        );
    }

    public function testRiddleSystemStateValidatesAutomaticAnswers(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());

        $this->assertSame(
            'invalide',
            $service->calculateRiddleSystemState(true, 'termine', 'immediat', null, 'automatique', false, 1000)
        );
        $this->assertSame(
            'accessible',
            $service->calculateRiddleSystemState(true, 'termine', 'immediat', null, 'automatique', true, 1000)
        );
        $this->assertSame(
            'accessible',
            $service->calculateRiddleSystemState(true, 'en_cours', 'date_programmee', 500, 'manuelle', false, 1000)
        );
    }

    public function testRiddleStatusDeletionIsValidatedAndDelegated(): void
    {
        $repository = new HuntProgressRepositoryStub();
        $repository->deletedStatuses = 4;
        $service = new HuntProgressService($repository);

        $this->assertSame(4, $service->deleteRiddleStatuses(10));
        $this->assertSame([10], $repository->statusArguments);
        $this->assertSame(0, $service->deleteRiddleStatuses(0));
    }

    public function testRepositoryDeletesStatusesForRiddle(): void
    {
        $wpdb = new class {
            public string $prefix = 'wp_';
            public $deleteResult = 3;
            public array $deleteArguments = [];

            public function delete(string $table, array $where, array $whereFormat)
            {
                $this->deleteArguments = [$table, $where, $whereFormat];
                return $this->deleteResult;
            }
        };
        $repository = new HuntProgressRepository($wpdb);

        $this->assertSame(3, $repository->deleteStatusesForRiddle(10));
        $this->assertSame(
            ['wp_enigme_statuts_utilisateur', ['enigme_id' => 10], ['%d']],
            $wpdb->deleteArguments
        );

        $wpdb->deleteResult = false;
        $this->assertSame(0, $repository->deleteStatusesForRiddle(10));
    }

    public function testRiddleStatusCanOnlyAdvanceUnlessForced(): void
    {
        $repository = new HuntProgressRepositoryStub();
        $repository->status = 'en_cours';
        $service = new HuntProgressService($repository);

        $this->assertTrue($service->advanceRiddleStatus(7, 10, 'resolue', '2026-09-29 12:00:00'));
        $this->assertSame([7, 10, 'resolue', '2026-09-29 12:00:00', true], $repository->persistedStatus);
        $this->assertFalse($service->advanceRiddleStatus(7, 10, 'soumis', '2026-09-29 12:00:00'));
        $this->assertTrue($service->advanceRiddleStatus(7, 10, 'soumis', '2026-09-29 12:00:00', true));
    }

    public function testNewRiddleStatusIsInsertedAndInvalidInputIsRejected(): void
    {
        $repository = new HuntProgressRepositoryStub();
        $repository->status = null;
        $service = new HuntProgressService($repository);

        $this->assertTrue($service->advanceRiddleStatus(7, 10, 'en_cours', '2026-09-29 12:00:00'));
        $this->assertSame([7, 10, 'en_cours', '2026-09-29 12:00:00', false], $repository->persistedStatus);
        $this->assertFalse($service->advanceRiddleStatus(0, 10, 'en_cours', '2026-09-29 12:00:00'));
        $this->assertFalse($service->advanceRiddleStatus(7, 10, 'inconnu', '2026-09-29 12:00:00'));
    }

    public function testCompletedUsersComeFromProgressRepository(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());
        $users = $service->getCompletedUsers([10, 11], [12]);

        $this->assertSame(7, $users[0]->user_id);
        $this->assertSame('2026-09-29 10:00:00', $users[0]->first_finish);
    }

    public function testSolvedRiddleCountComesFromProgressRepository(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());

        $this->assertSame(2, $service->countSolvedRiddles(7, [10, 11]));
        $this->assertSame(0, $service->countSolvedRiddles(0, [10, 11]));
    }

    public function testEngagedRiddleCountComesFromProgressRepository(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());

        $this->assertSame(1, $service->countEngagedRiddles(7, [10, 11]));
        $this->assertSame(0, $service->countEngagedRiddles(0, [10, 11]));
    }

    public function testValidatableRiddleCountComesFromProgressRepository(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());

        $this->assertSame(2, $service->countValidatableRiddles([10, 11, 12]));
    }

    public function testCompleteRiddlesDelegatesStorageToRepository(): void
    {
        $repository = new HuntProgressRepositoryStub();
        $service = new HuntProgressService($repository);
        $usersByRiddle = $service->completeRiddles([10], '2026-09-29 11:00:00');

        $this->assertSame([10 => [7, 8]], $usersByRiddle);
        $this->assertSame([[10], '2026-09-29 11:00:00'], $repository->completedRiddles);
    }
}
