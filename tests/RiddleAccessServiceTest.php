<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleAccessService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleAccessService.php';

class RiddleAccessServiceTest extends TestCase
{
    private RiddleAccessService $service;

    protected function setUp(): void
    {
        $this->service = new RiddleAccessService();
    }

    public function testAdministratorCanViewRiddleWithoutHunt(): void
    {
        $this->assertTrue($this->canView(isAdministrator: true, hasAssociatedHunt: false));
    }

    public function testRiddleWithoutHuntIsHidden(): void
    {
        $this->assertFalse($this->canView(hasAssociatedHunt: false));
    }

    public function testPublishedRiddleIsPublicAfterHuntEnds(): void
    {
        $this->assertTrue($this->canView(isHuntFinished: true));
        $this->assertFalse($this->canView(isHuntFinished: true, publicationStatus: 'pending'));
    }

    public function testEngagedPlayerNeedsPublishedAccessibleRiddle(): void
    {
        $this->assertTrue($this->canView(isEngagedInHunt: true));
        $this->assertFalse($this->canView(isEngagedInHunt: true, systemStatus: 'bloquee_date'));
    }

    public function testOrganizerCanPreviewPendingRiddleDuringEditingWorkflow(): void
    {
        $this->assertTrue($this->canView(
            publicationStatus: 'pending',
            validationStatus: 'correction',
            isAssociatedOrganizer: true,
            isEngagedInHunt: true
        ));
    }

    public function testSubscriberNeedsPublishedAccessibleRiddle(): void
    {
        $this->assertTrue($this->canView(isSubscriber: true));
        $this->assertFalse($this->canView(isSubscriber: true, publicationStatus: 'draft'));
    }

    /**
     * @dataProvider organizerSystemStatusProvider
     */
    public function testOrganizerCanViewPublishedLockedRiddle(string $systemStatus): void
    {
        $this->assertTrue($this->canView(
            systemStatus: $systemStatus,
            validationStatus: 'valide',
            isAssociatedOrganizer: true
        ));
    }

    /** @return array<string, array{string}> */
    public function organizerSystemStatusProvider(): array
    {
        return [
            'hunt lock' => ['bloquee_chasse'],
            'prerequisite lock' => ['bloquee_pre_requis'],
            'date lock' => ['bloquee_date'],
        ];
    }

    public function testUnrelatedVisitorCannotViewRiddle(): void
    {
        $this->assertFalse($this->canView());
    }

    private function canView(
        bool $isAdministrator = false,
        bool $hasAssociatedHunt = true,
        bool $isHuntFinished = false,
        string $publicationStatus = 'publish',
        string $systemStatus = 'accessible',
        string $validationStatus = 'valide',
        bool $isAssociatedOrganizer = false,
        bool $isEngagedInHunt = false,
        bool $isSubscriber = false
    ): bool {
        return $this->service->canView(
            $isAdministrator,
            $hasAssociatedHunt,
            $isHuntFinished,
            $publicationStatus,
            $systemStatus,
            $validationStatus,
            $isAssociatedOrganizer,
            $isEngagedInHunt,
            $isSubscriber
        );
    }
}
