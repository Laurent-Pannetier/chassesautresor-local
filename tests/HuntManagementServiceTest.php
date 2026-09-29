<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntManagementService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntManagementService.php';

class HuntManagementServiceTest extends TestCase
{
    private HuntManagementService $service;

    protected function setUp(): void
    {
        $this->service = new HuntManagementService();
    }

    public function testHuntInCreationRequiresAllCreationStatuses(): void
    {
        $this->assertTrue($this->service->isInCreation('pending', 'creation', 'revision'));
        $this->assertFalse($this->service->isInCreation('publish', 'creation', 'revision'));
        $this->assertFalse($this->service->isInCreation('pending', 'valide', 'revision'));
        $this->assertFalse($this->service->isInCreation('pending', 'creation', 'en_cours'));
    }

    public function testPublishedOrganizerCanCreateHuntWithoutPendingHunt(): void
    {
        $this->assertTrue($this->canCreate());
    }

    public function testPublishedOrganizerCannotCreateSecondPendingHunt(): void
    {
        $this->assertFalse($this->canCreate(hasPendingHunt: true));
    }

    public function testUnpublishedOrganizerCanCreateDespitePendingHunt(): void
    {
        $this->assertTrue($this->canCreate(isOrganizerPublished: false, hasPendingHunt: true));
    }

    public function testOrganizerBeingCreatedCanCreateOnlyFirstHunt(): void
    {
        $this->assertTrue($this->canCreate(
            hasOrganizerRole: false,
            hasOrganizerCreationRole: true,
            isOrganizerPublished: false
        ));
        $this->assertFalse($this->canCreate(
            hasOrganizerRole: false,
            hasOrganizerCreationRole: true,
            isOrganizerPublished: false,
            hasExistingHunt: true
        ));
    }

    /**
     * @dataProvider deniedContextProvider
     */
    public function testInvalidContextPreventsHuntCreation(array $overrides): void
    {
        $this->assertFalse($this->canCreate(...$overrides));
    }

    /** @return array<string, array{array<string, bool>}> */
    public function deniedContextProvider(): array
    {
        return [
            'guest' => [['isAuthenticated' => false]],
            'administrator' => [['isAdministrator' => true]],
            'unrelated organizer' => [['canManageOrganizer' => false]],
            'unsupported role' => [['hasOrganizerRole' => false]],
        ];
    }

    private function canCreate(
        bool $isAuthenticated = true,
        bool $isAdministrator = false,
        bool $canManageOrganizer = true,
        bool $hasOrganizerRole = true,
        bool $hasOrganizerCreationRole = false,
        bool $isOrganizerPublished = true,
        bool $hasPendingHunt = false,
        bool $hasExistingHunt = false
    ): bool {
        return $this->service->canCreate(
            $isAuthenticated,
            $isAdministrator,
            $canManageOrganizer,
            $hasOrganizerRole,
            $hasOrganizerCreationRole,
            $isOrganizerPublished,
            $hasPendingHunt,
            $hasExistingHunt
        );
    }
}
