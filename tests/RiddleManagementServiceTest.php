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
