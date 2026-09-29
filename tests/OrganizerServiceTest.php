<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Relationships\OrganizerRepository;
use ChassesAuTresor\Core\Relationships\OrganizerService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/OrganizerRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/OrganizerService.php';

class OrganizerRepositoryStub extends OrganizerRepository
{
    public int $userId = 0;

    public function __construct()
    {
    }

    public function findIdForUser(int $userId): ?int
    {
        $this->userId = $userId;

        return 42;
    }
}

class OrganizerServiceTest extends TestCase
{
    public function testOrganizerLookupIsDelegatedForAValidUser(): void
    {
        $repository = new OrganizerRepositoryStub();
        $service = new OrganizerService($repository);

        $this->assertSame(42, $service->findIdForUser(7));
        $this->assertSame(7, $repository->userId);
    }

    public function testInvalidUserDoesNotReachRepository(): void
    {
        $repository = new OrganizerRepositoryStub();
        $service = new OrganizerService($repository);

        $this->assertNull($service->findIdForUser(0));
        $this->assertSame(0, $repository->userId);
    }
}
