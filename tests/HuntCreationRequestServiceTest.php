<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntCreationRequestService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntCreationRequestService.php';

final class HuntCreationRequestServiceTest extends TestCase {
    private HuntCreationRequestService $service;

    protected function setUp(): void {
        $this->service = new HuntCreationRequestService();
    }

    public function testRequiresAnOrganizer(): void {
        $this->assertSame('missing_organizer', $this->error(0));
    }

    public function testCreationOnlyRoleCannotCreateASecondHunt(): void {
        $this->assertSame('hunt_limit_reached', $this->error(12, false, false, true, true));
    }

    public function testRejectsUsersWithoutAnAuthorizedRole(): void {
        $this->assertSame('access_denied', $this->error(12));
    }

    public function testPublishedOrganizerCannotHaveTwoPendingHunts(): void {
        $this->assertSame(
            'pending_hunt_exists',
            $this->error(12, false, true, false, false, false, true, true)
        );
    }

    public function testManagersAndOrganizersCanCreateAHunt(): void {
        $this->assertNull($this->error(12, true));
        $this->assertNull($this->error(12, false, true));
        $this->assertNull($this->error(12, false, true, false, false, true, true, true));
    }

    private function error(
        int $organizerId,
        bool $isAdministrator = false,
        bool $isOrganizer = false,
        bool $hasCreationRole = false,
        bool $organizerHasHunts = false,
        bool $canManageOptions = false,
        bool $organizerIsPublished = false,
        bool $organizerHasPendingHunt = false
    ): ?string {
        return $this->service->getError(
            $organizerId,
            $isAdministrator,
            $isOrganizer,
            $hasCreationRole,
            $organizerHasHunts,
            $canManageOptions,
            $organizerIsPublished,
            $organizerHasPendingHunt
        );
    }
}
