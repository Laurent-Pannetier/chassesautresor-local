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

    public function __construct()
    {
    }

    public function insert(array $attempt): bool
    {
        $this->attempt = $attempt;
        return true;
    }

    public function findByUid(string $uid): ?object
    {
        $this->uid = $uid;
        return (object) ['tentative_uid' => $uid];
    }
}

class RiddleAttemptServiceTest extends TestCase
{
    public function testAttemptCreationIsValidatedAndDelegated(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);

        $this->assertTrue($service->create('uid-1', 7, 10, 'réponse', 'bon', -2, '127.0.0.1', 'Agent'));
        $this->assertSame(0, $repository->attempt['points_utilises']);
        $this->assertSame('réponse', $repository->attempt['reponse_saisie']);
        $this->assertFalse($service->create('', 7, 10, 'réponse', 'bon', 0, null, null));
    }

    public function testAttemptLookupTrimsUidAndRejectsEmptyValues(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);

        $this->assertSame('uid-1', $service->findByUid(' uid-1 ')->tentative_uid);
        $this->assertSame('uid-1', $repository->uid);
        $this->assertNull($service->findByUid('  '));
    }
}
