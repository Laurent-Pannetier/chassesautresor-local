<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RelatedContentActionService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RelatedContentActionService.php';

class RelatedContentActionServiceTest extends TestCase
{
    private RelatedContentActionService $service;

    protected function setUp(): void
    {
        $this->service = new RelatedContentActionService();
    }

    /**
     * @dataProvider allowedHuntActionProvider
     */
    public function testOrganizerCanManageRelatedHuntContent(array $overrides): void
    {
        $this->assertTrue($this->canPerform(...$overrides));
    }

    /** @return array<string, array{array<string, string>}> */
    public function allowedHuntActionProvider(): array
    {
        return [
            'create' => [['action' => 'create']],
            'edit' => [['action' => 'edit']],
            'delete' => [['action' => 'delete', 'publicationStatus' => 'draft']],
        ];
    }

    /**
     * @dataProvider allowedRiddleActionProvider
     */
    public function testOrganizerCanManageRelatedRiddleContent(array $overrides): void
    {
        $this->assertTrue($this->canPerform(...$overrides));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public function allowedRiddleActionProvider(): array
    {
        return [
            'create' => [[
                'action' => 'create',
                'objectType' => 'enigme',
                'hasHunt' => true,
                'isHuntActionAllowed' => true,
            ]],
            'edit' => [[
                'action' => 'edit',
                'objectType' => 'enigme',
                'hasHunt' => true,
                'isHuntActionAllowed' => true,
            ]],
            'delete' => [[
                'action' => 'delete',
                'objectType' => 'enigme',
                'publicationStatus' => 'draft',
                'hasHunt' => true,
            ]],
        ];
    }

    /**
     * @dataProvider deniedActionProvider
     */
    public function testInvalidContextPreventsAction(array $overrides): void
    {
        $this->assertFalse($this->canPerform(...$overrides));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public function deniedActionProvider(): array
    {
        return [
            'guest administrator' => [['isAuthenticated' => false, 'isAdministrator' => true]],
            'invalid object' => [['isValidObject' => false]],
            'unknown action' => [['action' => 'archive']],
            'unrelated user' => [['isAssociatedOrganizer' => false]],
            'hunt draft creation' => [['publicationStatus' => 'draft']],
            'hunt invalid validation' => [['validationStatus' => 'banni']],
            'riddle without hunt' => [['objectType' => 'enigme']],
            'riddle draft edition' => [[
                'action' => 'edit',
                'objectType' => 'enigme',
                'publicationStatus' => 'draft',
                'hasHunt' => true,
                'isHuntActionAllowed' => true,
            ]],
            'riddle denied by hunt' => [[
                'objectType' => 'enigme',
                'hasHunt' => true,
            ]],
            'unsupported object' => [['objectType' => 'organisateur']],
        ];
    }

    private function canPerform(
        bool $isAuthenticated = true,
        string $action = 'create',
        string $objectType = 'chasse',
        bool $isValidObject = true,
        bool $isAdministrator = false,
        bool $isAssociatedOrganizer = true,
        string $publicationStatus = 'pending',
        string $validationStatus = 'creation',
        bool $hasHunt = false,
        bool $isHuntActionAllowed = false
    ): bool {
        return $this->service->canPerform(
            $isAuthenticated,
            $action,
            $objectType,
            $isValidObject,
            $isAdministrator,
            $isAssociatedOrganizer,
            $publicationStatus,
            $validationStatus,
            $hasHunt,
            $isHuntActionAllowed
        );
    }
}
