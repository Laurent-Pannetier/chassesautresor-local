<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntValidationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntValidationService.php';

class HuntValidationServiceTest extends TestCase
{
    private HuntValidationService $service;

    protected function setUp(): void
    {
        $this->service = new HuntValidationService();
    }

    public function testCompleteAssociatedOrganizerCanRequestValidation(): void
    {
        $this->assertTrue($this->canRequestValidation());
        $this->assertTrue($this->canRequestValidation(validationStatus: 'correction'));
    }

    /**
     * @dataProvider invalidContextProvider
     */
    public function testInvalidContextPreventsValidationRequest(array $overrides): void
    {
        $this->assertFalse($this->canRequestValidation(...$overrides));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public function invalidContextProvider(): array
    {
        return [
            'user is not an organizer' => [['isOrganizer' => false]],
            'organizer is not associated' => [['isAssociatedOrganizer' => false]],
            'organizer profile is incomplete' => [['isOrganizerComplete' => false]],
            'hunt is incomplete' => [['isHuntComplete' => false]],
            'hunt is not pending' => [['publicationStatus' => 'publish']],
            'validation is already pending' => [['validationStatus' => 'en_attente']],
            'hunt is not in revision' => [['functionalStatus' => 'a_venir']],
            'hunt has no riddle' => [['riddles' => []]],
            'riddle is not locked by hunt' => [[
                'riddles' => [['system_status' => 'accessible', 'is_complete' => true]],
            ]],
            'riddle is incomplete' => [[
                'riddles' => [['system_status' => 'bloquee_chasse', 'is_complete' => false]],
            ]],
        ];
    }

    /**
     * @param array<int, array{system_status:string,is_complete:bool}>|null $riddles
     */
    private function canRequestValidation(
        bool $isOrganizer = true,
        bool $isAssociatedOrganizer = true,
        bool $isOrganizerComplete = true,
        bool $isHuntComplete = true,
        string $publicationStatus = 'pending',
        string $validationStatus = 'creation',
        string $functionalStatus = 'revision',
        ?array $riddles = null
    ): bool {
        return $this->service->canRequestValidation(
            $isOrganizer,
            $isAssociatedOrganizer,
            $isOrganizerComplete,
            $isHuntComplete,
            $publicationStatus,
            $validationStatus,
            $functionalStatus,
            $riddles ?? [['system_status' => 'bloquee_chasse', 'is_complete' => true]]
        );
    }
}
