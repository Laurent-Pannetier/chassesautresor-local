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

    /**
     * @dataProvider organizerIdProvider
     *
     * @param mixed $value
     */
    public function testOrganizerIdIsNormalized($value, ?int $expected): void
    {
        $service = new OrganizerService(new OrganizerRepositoryStub());

        $this->assertSame($expected, $service->normalizeId($value));
    }

    public function organizerIdProvider(): array
    {
        return [
            'integer' => [42, 42],
            'numeric string' => ['42', 42],
            'object' => [(object) ['ID' => 42], 42],
            'array of IDs' => [[42], 42],
            'array of objects' => [[(object) ['ID' => 42]], 42],
            'empty array' => [[], null],
            'zero' => [0, null],
            'invalid object' => [(object) ['post_id' => 42], null],
        ];
    }

    public function testAssociatedUserAcceptsIdsAndObjects(): void
    {
        $service = new OrganizerService(new OrganizerRepositoryStub());
        $users = [12, (object) ['ID' => 24], (object) ['post_id' => 36], 'invalid'];

        $this->assertTrue($service->isUserAssociated(12, $users));
        $this->assertTrue($service->isUserAssociated(24, $users));
        $this->assertFalse($service->isUserAssociated(36, $users));
        $this->assertFalse($service->isUserAssociated(0, $users));
    }
}
