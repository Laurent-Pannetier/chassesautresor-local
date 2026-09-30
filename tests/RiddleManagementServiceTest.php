<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleManagementService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleManagementService.php';

class RiddleManagementServiceTest extends TestCase
{
    private RiddleManagementService $service;

    protected function setUp(): void
    {
        $this->service = new RiddleManagementService();
    }

    public function testAssociatedOrganizerCanAddRiddleToEditableHunt(): void
    {
        $this->assertTrue($this->canAdd());
        $this->assertTrue($this->canAdd(
            validationStatus: 'correction',
            riddleCount: RiddleManagementService::MAX_RIDDLES_PER_HUNT - 1
        ));
    }

    public function testReorderKeepsOnlyUniqueRiddlesFromHunt(): void
    {
        $this->assertSame(
            [24, 12],
            $this->service->getReorderUpdates([24, 99, 24, 0, 12], [12, 24, 36])
        );
    }

    public function testReorderRejectsEmptyOrUnrelatedSubmission(): void
    {
        $this->assertSame([], $this->service->getReorderUpdates([], [12, 24]));
        $this->assertSame([], $this->service->getReorderUpdates([99], [12, 24]));
    }

    /**
     * @dataProvider deniedAdditionProvider
     */
    public function testInvalidContextPreventsRiddleAddition(array $overrides): void
    {
        $this->assertFalse($this->canAdd(...$overrides));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public function deniedAdditionProvider(): array
    {
        return [
            'invalid hunt' => [['isHunt' => false]],
            'guest' => [['isAuthenticated' => false]],
            'non organizer' => [['isOrganizer' => false]],
            'published hunt' => [['huntPublicationStatus' => 'publish']],
            'active hunt' => [['huntStatus' => 'en_cours']],
            'pending validation' => [['validationStatus' => 'en_attente']],
            'unrelated organizer' => [['isAssociatedOrganizer' => false]],
            'riddle limit reached' => [['riddleCount' => RiddleManagementService::MAX_RIDDLES_PER_HUNT]],
        ];
    }

    public function testAssociatedOrganizerCanDeleteRiddleFromEditableHunt(): void
    {
        $this->assertTrue($this->canDelete());
        $this->assertTrue($this->canDelete(validationStatus: 'correction'));
    }

    public function testAdministratorCanEditRiddleWithoutHunt(): void
    {
        $this->assertTrue($this->service->canEdit(true, true, false, false));
    }

    public function testAssociatedOrganizerCanEditRiddle(): void
    {
        $this->assertTrue($this->service->canEdit(true, false, true, true));
    }

    /**
     * @dataProvider deniedEditionProvider
     */
    public function testInvalidContextPreventsRiddleEdition(array $context): void
    {
        $this->assertFalse($this->service->canEdit(...$context));
    }

    /** @return array<string, array{array{bool, bool, bool, bool}}> */
    public function deniedEditionProvider(): array
    {
        return [
            'invalid riddle for administrator' => [[false, true, true, true]],
            'missing hunt' => [[true, false, false, true]],
            'unrelated organizer' => [[true, false, true, false]],
        ];
    }

    /**
     * @dataProvider deniedDeletionProvider
     */
    public function testInvalidContextPreventsRiddleDeletion(array $overrides): void
    {
        $this->assertFalse($this->canDelete(...$overrides));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public function deniedDeletionProvider(): array
    {
        return [
            'invalid riddle' => [['isRiddle' => false]],
            'guest' => [['isAuthenticated' => false]],
            'non organizer' => [['isOrganizer' => false]],
            'missing hunt' => [['hasHunt' => false]],
            'active hunt' => [['huntStatus' => 'en_cours']],
            'pending validation' => [['validationStatus' => 'en_attente']],
            'unrelated organizer' => [['isAssociatedOrganizer' => false]],
        ];
    }

    private function canAdd(
        bool $isHunt = true,
        bool $isAuthenticated = true,
        bool $isOrganizer = true,
        string $huntPublicationStatus = 'draft',
        string $huntStatus = 'revision',
        string $validationStatus = 'creation',
        bool $isAssociatedOrganizer = true,
        int $riddleCount = 0
    ): bool {
        return $this->service->canAdd(
            $isHunt,
            $isAuthenticated,
            $isOrganizer,
            $huntPublicationStatus,
            $huntStatus,
            $validationStatus,
            $isAssociatedOrganizer,
            $riddleCount
        );
    }

    private function canDelete(
        bool $isRiddle = true,
        bool $isAuthenticated = true,
        bool $isOrganizer = true,
        bool $hasHunt = true,
        string $huntStatus = 'revision',
        string $validationStatus = 'creation',
        bool $isAssociatedOrganizer = true
    ): bool {
        return $this->service->canDelete(
            $isRiddle,
            $isAuthenticated,
            $isOrganizer,
            $hasHunt,
            $huntStatus,
            $validationStatus,
            $isAssociatedOrganizer
        );
    }
}
