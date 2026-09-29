<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\ContentCreationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/ContentCreationService.php';

class ContentCreationServiceTest extends TestCase
{
    private ContentCreationService $service;

    protected function setUp(): void
    {
        $this->service = new ContentCreationService();
    }

    public function testAdministratorCanCreateSupportedOrUnknownContent(): void
    {
        $this->assertTrue($this->canCreate(
            isAdministrator: true,
            contentType: 'inconnu'
        ));
    }

    public function testUserWithoutOrganizerCanCreateOrganizer(): void
    {
        $this->assertTrue($this->canCreate(contentType: 'organisateur'));
        $this->assertFalse($this->canCreate(contentType: 'organisateur', hasOrganizer: true));
    }

    public function testOrganizerCanCreateSeveralHunts(): void
    {
        $this->assertTrue($this->canCreate(
            contentType: 'chasse',
            hasOrganizer: true,
            hasOrganizerRole: true,
            hasExistingHunt: true
        ));
    }

    public function testUserWithoutOrganizerRoleCanOnlyCreateFirstHunt(): void
    {
        $this->assertTrue($this->canCreate(contentType: 'chasse', hasOrganizer: true));
        $this->assertFalse($this->canCreate(
            contentType: 'chasse',
            hasOrganizer: true,
            hasExistingHunt: true
        ));
    }

    public function testUserCanCreateRiddleForOwnHuntInCreation(): void
    {
        $this->assertTrue($this->canCreate(
            contentType: 'enigme',
            hasValidHunt: true,
            hasSameOrganizer: true,
            huntValidationStatus: ' creation '
        ));
    }

    /**
     * @dataProvider deniedCreationProvider
     */
    public function testInvalidContextPreventsCreation(array $overrides): void
    {
        $this->assertFalse($this->canCreate(...$overrides));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public function deniedCreationProvider(): array
    {
        return [
            'guest administrator' => [['isAuthenticated' => false, 'isAdministrator' => true]],
            'hunt without organizer' => [['contentType' => 'chasse']],
            'riddle without hunt' => [['contentType' => 'enigme']],
            'riddle for other organizer' => [[
                'contentType' => 'enigme',
                'hasValidHunt' => true,
                'huntValidationStatus' => 'creation',
            ]],
            'riddle outside creation' => [[
                'contentType' => 'enigme',
                'hasValidHunt' => true,
                'hasSameOrganizer' => true,
                'huntValidationStatus' => 'correction',
            ]],
            'unsupported content' => [['contentType' => 'solution']],
        ];
    }

    private function canCreate(
        bool $isAuthenticated = true,
        bool $isAdministrator = false,
        string $contentType = 'organisateur',
        bool $hasOrganizer = false,
        bool $hasOrganizerRole = false,
        bool $hasExistingHunt = false,
        bool $hasValidHunt = false,
        bool $hasSameOrganizer = false,
        string $huntValidationStatus = ''
    ): bool {
        return $this->service->canCreate(
            $isAuthenticated,
            $isAdministrator,
            $contentType,
            $hasOrganizer,
            $hasOrganizerRole,
            $hasExistingHunt,
            $hasValidHunt,
            $hasSameOrganizer,
            $huntValidationStatus
        );
    }
}
