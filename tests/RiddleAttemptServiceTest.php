<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAttemptRepository;
use ChassesAuTresor\Core\Progress\RiddleAttemptService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAttemptRepository.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAttemptService.php';

class RiddleAttemptRepositoryStub extends RiddleAttemptRepository
{
    public array $attempt = [];
    public string $uid = '';
    public array $countArguments = [];
    public ?object $foundAttempt = null;
    public bool $returnConfiguredAttempt = false;
    public array $listArguments = [];
    public array $attempts = [];
    public ?object $latestPendingAttempt = null;
    public int $deletedAttempts = 0;
    public int $lastInsertId = 0;
    public ?string $userRiddleStatus = null;
    public array $processArguments = [];
    public bool $processResult = true;
    public bool $hasSuccessfulAttempt = false;
    public array $pendingCounts = [];
    public array $pendingRiddleIds = [];

    public function __construct()
    {
    }

    public function insert(array $attempt): bool
    {
        $this->attempt = $attempt;
        return true;
    }

    public function getLastInsertId(): int
    {
        return $this->lastInsertId;
    }

    public function findByUid(string $uid): ?object
    {
        $this->uid = $uid;

        if ($this->returnConfiguredAttempt) {
            return $this->foundAttempt;
        }

        return (object) ['tentative_uid' => $uid];
    }

    public function countForRiddle(int $riddleId): int
    {
        $this->countArguments = ['riddle' => $riddleId];
        return 12;
    }

    public function findForRiddle(int $riddleId, int $limit, int $offset): array
    {
        $this->listArguments = [$riddleId, $limit, $offset];
        return $this->attempts;
    }

    public function findLatestPendingForUserAndRiddle(int $userId, int $riddleId): ?object
    {
        $this->listArguments = [$userId, $riddleId];
        return $this->latestPendingAttempt;
    }

    public function deleteForRiddle(int $riddleId): int
    {
        $this->listArguments = [$riddleId];
        return $this->deletedAttempts;
    }

    public function deleteForStep(int $stepId): int
    {
        $this->listArguments = ['step' => $stepId];
        return $this->deletedAttempts;
    }

    public function findUserRiddleStatus(int $userId, int $riddleId): ?string
    {
        $this->countArguments = [$userId, $riddleId];
        return $this->userRiddleStatus;
    }

    public function markPendingAsProcessed(string $uid, string $result): bool
    {
        $this->processArguments = [$uid, $result];
        return $this->processResult;
    }

    public function hasSuccessfulAttempt(int $userId, int $riddleId): bool
    {
        $this->countArguments = [$userId, $riddleId];
        return $this->hasSuccessfulAttempt;
    }

    public function countPendingForRiddle(int $riddleId): int
    {
        $this->countArguments = ['pending' => $riddleId];
        $this->pendingRiddleIds[] = $riddleId;
        return $this->pendingCounts[$riddleId] ?? 3;
    }

    public function countForUserAndRiddleBetween(
        int $userId,
        int $riddleId,
        string $startAt,
        string $endAt
    ): int {
        $this->countArguments = [$userId, $riddleId, $startAt, $endAt];
        return 2;
    }

    public function countFailuresForUserAndRiddleBetween(
        int $userId,
        int $riddleId,
        string $startAt,
        string $endAt
    ): int {
        $this->countArguments = [$userId, $riddleId, $startAt, $endAt, 'failures'];
        return 1;
    }
}

class RiddleAttemptServiceTest extends TestCase
{
    public function testDailyFailureCountUsesTheParisCalendarDay(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);
        $now = new DateTimeImmutable('2026-10-02 14:30:00', new DateTimeZone('UTC'));

        $this->assertSame(1, $service->countFailuresTodayForUser(7, 10, $now));
        $this->assertSame(
            [7, 10, '2026-10-02 00:00:00', '2026-10-02 23:59:59', 'failures'],
            $repository->countArguments
        );
        $this->assertSame(0, $service->countFailuresTodayForUser(0, 10, $now));
    }

    public function testAttemptCreationIsValidatedAndDelegated(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);

        $this->assertTrue($service->create('uid-1', 7, 10, 'réponse', 'bon', -2, '127.0.0.1', 'Agent'));
        $this->assertSame(0, $repository->attempt['points_utilises']);
        $this->assertSame('réponse', $repository->attempt['reponse_saisie']);
        $this->assertFalse($service->create('', 7, 10, 'réponse', 'bon', 0, null, null));
    }

    public function testClickInteractionIsStoredWithoutCost(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);

        $this->assertTrue($service->createForStep('uid-2', 7, 10, 12, 'click', 'bon', null, null));
        $this->assertSame(12, $repository->attempt['etape_id']);
        $this->assertSame('bon', $repository->attempt['resultat']);
        $this->assertSame(0, $repository->attempt['points_utilises']);
        $this->assertFalse($service->createForStep('', 7, 10, 12, 'click', 'bon', null, null));
    }

    public function testLastCreatedIdIsDelegated(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $repository->lastInsertId = 42;
        $service = new RiddleAttemptService($repository);

        $this->assertSame(42, $service->getLastCreatedId());
    }

    public function testAttemptLookupTrimsUidAndRejectsEmptyValues(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);

        $this->assertSame('uid-1', $service->findByUid(' uid-1 ')->tentative_uid);
        $this->assertSame('uid-1', $repository->uid);
        $this->assertNull($service->findByUid('  '));
    }

    /**
     * @dataProvider attemptStateProvider
     */
    public function testAttemptStateIsDerivedFromStoredResult(?string $result, string $expectedState): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $repository->returnConfiguredAttempt = true;
        $repository->foundAttempt = $result === null ? null : (object) ['resultat' => $result];
        $service = new RiddleAttemptService($repository);

        $this->assertSame($expectedState, $service->getStateByUid(' attempt-1 '));
        $this->assertSame('attempt-1', $repository->uid);
    }

    public function attemptStateProvider(): array
    {
        return [
            'missing attempt' => [null, 'inexistante'],
            'pending attempt' => ['attente', 'attente'],
            'successful attempt' => ['bon', 'validee'],
            'failed attempt' => ['faux', 'refusee'],
            'unknown result' => ['autre', 'invalide'],
        ];
    }

    public function testAttemptDescriptionCombinesPersistedAndDerivedState(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $repository->returnConfiguredAttempt = true;
        $repository->foundAttempt = (object) [
            'tentative_uid' => 'attempt-1',
            'resultat' => 'bon',
            'traitee' => '1',
        ];
        $service = new RiddleAttemptService($repository);

        $details = $service->describeByUid(' attempt-1 ');

        $this->assertSame($repository->foundAttempt, $details['attempt']);
        $this->assertSame('validee', $details['state']);
        $this->assertSame('bon', $details['result']);
        $this->assertTrue($details['processed']);
        $this->assertTrue($details['already_processed']);
        $this->assertTrue($details['just_processed']);
        $this->assertSame('attempt-1', $repository->uid);
    }

    public function testAttemptDescriptionHandlesPendingAndMissingAttempts(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $repository->returnConfiguredAttempt = true;
        $repository->foundAttempt = (object) ['resultat' => 'attente', 'traitee' => 0];
        $service = new RiddleAttemptService($repository);

        $details = $service->describeByUid('attempt-1');

        $this->assertSame('attente', $details['state']);
        $this->assertFalse($details['processed']);
        $this->assertFalse($details['already_processed']);
        $this->assertFalse($details['just_processed']);

        $repository->foundAttempt = null;
        $this->assertNull($service->describeByUid('attempt-2'));
    }

    public function testAttemptVisibilitySupportsOwnerAdministratorAndOrganizer(): void
    {
        $service = new RiddleAttemptService(new RiddleAttemptRepositoryStub());
        $attempt = (object) ['user_id' => 7, 'enigme_id' => 10];
        $organizerChecks = [];
        $organizerCheck = static function (int $userId, int $riddleId) use (&$organizerChecks): bool {
            $organizerChecks[] = [$userId, $riddleId];
            return $userId === 9;
        };

        $this->assertFalse($service->canViewAttempt($attempt, 0, true, $organizerCheck));
        $this->assertTrue($service->canViewAttempt($attempt, 7, false, $organizerCheck));
        $this->assertTrue($service->canViewAttempt($attempt, 8, true, $organizerCheck));
        $this->assertTrue($service->canViewAttempt($attempt, 9, false, $organizerCheck));
        $this->assertFalse($service->canViewAttempt($attempt, 10, false, $organizerCheck));
        $this->assertSame([[9, 10], [10, 10]], $organizerChecks);
    }

    public function testAttemptVisibilityRejectsMissingRiddleForUnrelatedUser(): void
    {
        $service = new RiddleAttemptService(new RiddleAttemptRepositoryStub());

        $this->assertFalse(
            $service->canViewAttempt(
                (object) ['user_id' => 7],
                8,
                false,
                static fn (int $userId, int $riddleId): bool => true
            )
        );
    }

    public function testManualAttemptProcessingIsLimitedToAdministratorsAndAssociatedOrganizers(): void
    {
        $service = new RiddleAttemptService(new RiddleAttemptRepositoryStub());
        $organizerUserIds = ['7', 9];

        $this->assertFalse($service->canProcessManualAttempt(0, true, $organizerUserIds));
        $this->assertTrue($service->canProcessManualAttempt(5, true, []));
        $this->assertTrue($service->canProcessManualAttempt(7, false, $organizerUserIds));
        $this->assertTrue($service->canProcessManualAttempt(9, false, $organizerUserIds));
        $this->assertFalse($service->canProcessManualAttempt(8, false, $organizerUserIds));
    }

    public function testManualAttemptWorkflowValidatesAndAtomicallyProcessesAttempt(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $repository->returnConfiguredAttempt = true;
        $repository->foundAttempt = (object) [
            'user_id' => 7,
            'enigme_id' => 10,
            'resultat' => 'attente',
        ];
        $service = new RiddleAttemptService($repository);

        $organizerUsers = static fn (int $riddleId): array => [9];
        $attempt = $service->processManualAttempt(' attempt-1 ', 'bon', 9, false, $organizerUsers);

        $this->assertSame($repository->foundAttempt, $attempt);
        $this->assertSame(['attempt-1', 'bon'], $repository->processArguments);

        $repository->foundAttempt->resultat = 'faux';
        $this->assertNull($service->processManualAttempt('attempt-1', 'bon', 9, false, $organizerUsers));

        $repository->foundAttempt->resultat = 'attente';
        $repository->userRiddleStatus = 'resolue';
        $this->assertNull($service->processManualAttempt('attempt-1', 'bon', 9, false, $organizerUsers));

        $repository->userRiddleStatus = null;
        $this->assertNull($service->processManualAttempt('attempt-1', 'bon', 8, false, $organizerUsers));
    }

    public function testManualNotificationPlanDeduplicatesRecipientsAndDefinesPresentation(): void
    {
        $service = new RiddleAttemptService(new RiddleAttemptRepositoryStub());

        $this->assertSame(
            [
                'approved' => true,
                'flash_type' => 'success',
                'persistent_recipient_ids' => [7, 9],
            ],
            $service->buildManualNotificationPlan(7, ['9', 7, 0], 'bon')
        );
        $this->assertSame(
            [
                'approved' => false,
                'flash_type' => 'error',
                'persistent_recipient_ids' => [7, 9],
            ],
            $service->buildManualNotificationPlan(7, [9], 'faux')
        );
    }

    public function testRiddleAttemptListIsValidatedAndDelegated(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $repository->attempts = [(object) ['tentative_uid' => 'attempt-1']];
        $service = new RiddleAttemptService($repository);

        $this->assertSame($repository->attempts, $service->findForRiddle(10, 20, 40));
        $this->assertSame([10, 20, 40], $repository->listArguments);
        $this->assertSame([], $service->findForRiddle(0, 20, 0));
        $this->assertSame([], $service->findForRiddle(10, 0, 0));
        $this->assertSame([], $service->findForRiddle(10, 20, -1));
    }

    public function testLatestPendingAttemptLookupIsValidatedAndDelegated(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $repository->latestPendingAttempt = (object) ['id' => 42];
        $service = new RiddleAttemptService($repository);

        $this->assertSame(
            $repository->latestPendingAttempt,
            $service->findLatestPendingForUserAndRiddle(7, 10)
        );
        $this->assertSame([7, 10], $repository->listArguments);
        $this->assertNull($service->findLatestPendingForUserAndRiddle(0, 10));
        $this->assertNull($service->findLatestPendingForUserAndRiddle(7, 0));
    }

    public function testRiddleAttemptDeletionIsValidatedAndDelegated(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $repository->deletedAttempts = 3;
        $service = new RiddleAttemptService($repository);

        $this->assertSame(3, $service->deleteForRiddle(10));
        $this->assertSame([10], $repository->listArguments);
        $this->assertSame(0, $service->deleteForRiddle(0));
    }

    public function testStepAttemptDeletionIsValidatedAndDelegated(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $repository->deletedAttempts = 2;
        $service = new RiddleAttemptService($repository);

        $this->assertSame(2, $service->deleteForStep(8));
        $this->assertSame(['step' => 8], $repository->listArguments);
        $this->assertSame(0, $service->deleteForStep(0));
    }

    public function testSolvedStateIsReadThroughRepository(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);

        $repository->userRiddleStatus = 'resolue';
        $this->assertTrue($service->isRiddleSolvedForUser(7, 10));
        $this->assertSame([7, 10], $repository->countArguments);

        $repository->userRiddleStatus = 'en_cours';
        $this->assertFalse($service->isRiddleSolvedForUser(7, 10));
        $this->assertFalse($service->isRiddleSolvedForUser(0, 10));
    }

    public function testPendingAttemptProcessingIsValidatedAndDelegated(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);

        $this->assertTrue($service->processPending(' attempt-1 ', 'bon'));
        $this->assertSame(['attempt-1', 'bon'], $repository->processArguments);
        $this->assertFalse($service->processPending('', 'bon'));
        $this->assertFalse($service->processPending('attempt-1', 'attente'));

        $repository->processResult = false;
        $this->assertFalse($service->processPending('attempt-1', 'faux'));
    }

    public function testSuccessfulAttemptLookupIsValidatedAndDelegated(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $repository->hasSuccessfulAttempt = true;
        $service = new RiddleAttemptService($repository);

        $this->assertTrue($service->hasSuccessfulAttempt(7, 10));
        $this->assertSame([7, 10], $repository->countArguments);
        $this->assertFalse($service->hasSuccessfulAttempt(0, 10));
        $this->assertFalse($service->hasSuccessfulAttempt(7, 0));
    }

    public function testAttemptCreationPolicyOnlyRejectsDuplicateSuccess(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);

        $repository->hasSuccessfulAttempt = true;
        $this->assertFalse($service->canCreateAttempt(7, 10, 'bon'));
        $this->assertTrue($service->canCreateAttempt(7, 10, 'faux'));
        $this->assertTrue($service->canCreateAttempt(7, 10, 'attente'));

        $repository->hasSuccessfulAttempt = false;
        $this->assertTrue($service->canCreateAttempt(7, 10, 'bon'));
    }

    public function testAttemptChargeIsNormalizedAndOnlyAppliedOnCreation(): void
    {
        $service = new RiddleAttemptService(new RiddleAttemptRepositoryStub());

        $this->assertSame(5, $service->getChargeAmount(5, true));
        $this->assertSame(0, $service->getChargeAmount(-5, true));
        $this->assertSame(0, $service->getChargeAmount(5, false));
    }

    public function testProcessingPlanCombinesCreationChargeAndOutcomePolicies(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);

        $repository->hasSuccessfulAttempt = true;
        $this->assertNull($service->buildProcessingPlan(7, 10, 'bon', 5, true, false));

        $repository->hasSuccessfulAttempt = false;
        $this->assertSame(
            [
                'charge' => 5,
                'outcome' => ['user_status' => 'resolue', 'resolved' => true, 'notify' => true],
            ],
            $service->buildProcessingPlan(7, 10, 'bon', 5, true, false)
        );
        $this->assertSame(
            [
                'charge' => 0,
                'outcome' => ['user_status' => 'echouee', 'resolved' => false, 'notify' => true],
            ],
            $service->buildProcessingPlan(7, 10, 'faux', 5, false, true)
        );
    }

    /**
     * @dataProvider attemptOutcomeProvider
     */
    public function testAttemptOutcomeDefinesStatusAndNotification(
        string $result,
        bool $notifyFailure,
        array $expected
    ): void {
        $service = new RiddleAttemptService(new RiddleAttemptRepositoryStub());

        $this->assertSame($expected, $service->getOutcome($result, $notifyFailure));
    }

    public function attemptOutcomeProvider(): array
    {
        return [
            'success' => [
                'bon',
                false,
                ['user_status' => 'resolue', 'resolved' => true, 'notify' => true],
            ],
            'failure without notification' => [
                'faux',
                false,
                ['user_status' => 'echouee', 'resolved' => false, 'notify' => false],
            ],
            'failure with notification' => [
                'faux',
                true,
                ['user_status' => 'echouee', 'resolved' => false, 'notify' => true],
            ],
            'pending result' => [
                'attente',
                true,
                ['user_status' => 'en_cours', 'resolved' => false, 'notify' => false],
            ],
        ];
    }

    public function testRiddleCountersAreValidatedAndDelegated(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);

        $this->assertSame(12, $service->countForRiddle(10));
        $this->assertSame(['riddle' => 10], $repository->countArguments);
        $this->assertSame(3, $service->countPendingForRiddle(10));
        $this->assertSame(['pending' => 10], $repository->countArguments);
        $this->assertSame(0, $service->countForRiddle(0));
        $this->assertSame(0, $service->countPendingForRiddle(-1));
    }

    public function testPendingManualRiddlesAreFilteredInInputOrder(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $repository->pendingCounts = [10 => 2, 12 => 0, 13 => 1];
        $service = new RiddleAttemptService($repository);

        $this->assertSame(
            [10, 13],
            $service->findPendingManualRiddleIds([
                10 => 'manuelle',
                11 => 'automatique',
                12 => 'manuelle',
                0 => 'manuelle',
                13 => 'manuelle',
            ])
        );
        $this->assertSame([10, 12, 13], $repository->pendingRiddleIds);
    }

    public function testDailyCounterUsesParisCalendarDay(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);
        $now = new DateTimeImmutable('2026-09-29 22:30:00', new DateTimeZone('UTC'));

        $this->assertSame(2, $service->countTodayForUser(7, 10, $now));
        $this->assertSame(
            [7, 10, '2026-09-30 00:00:00', '2026-09-30 23:59:59'],
            $repository->countArguments
        );
        $this->assertSame(0, $service->countTodayForUser(0, 10, $now));
        $this->assertSame(0, $service->countTodayForUser(7, 0, $now));
    }
}
