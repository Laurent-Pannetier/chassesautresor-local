<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\OrganizerRoleService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/OrganizerRoleService.php';

class OrganizerRoleServiceTest extends TestCase
{
    private OrganizerRoleService $service;

    protected function setUp(): void
    {
        $this->service = new OrganizerRoleService();
    }

    public function testConfirmedOrganizerRoleIsAccepted(): void
    {
        $this->assertTrue($this->isOrganizer(['subscriber', 'organisateur']));
    }

    public function testOrganizerCreationRoleIsAccepted(): void
    {
        $this->assertTrue($this->isOrganizer(['organisateur_en_creation']));
    }

    public function testUnrelatedRolesAreRejected(): void
    {
        $this->assertFalse($this->isOrganizer(['subscriber', 'customer']));
    }

    public function testEmptyRoleListIsRejected(): void
    {
        $this->assertFalse($this->isOrganizer([]));
    }

    public function testRoleComparisonIsStrict(): void
    {
        $this->assertFalse($this->isOrganizer([10], '10', '20'));
    }

    private function isOrganizer(
        array $roles,
        string $organizerRole = 'organisateur',
        string $organizerCreationRole = 'organisateur_en_creation'
    ): bool {
        return $this->service->isOrganizer($roles, $organizerRole, $organizerCreationRole);
    }
}
