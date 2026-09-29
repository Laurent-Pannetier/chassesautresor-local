<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\ContentFieldAccessService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/ContentFieldAccessService.php';

class ContentFieldAccessServiceTest extends TestCase
{
    private ContentFieldAccessService $service;

    protected function setUp(): void
    {
        $this->service = new ContentFieldAccessService();
    }

    public function testAdministratorCanEditFieldsOnVisiblePanel(): void
    {
        $this->assertTrue($this->canEdit(
            isAdministrator: true,
            contentType: 'inconnu'
        ));
    }

    /**
     * @dataProvider editableContentProvider
     */
    public function testEditableContentIsAllowed(array $overrides): void
    {
        $this->assertTrue($this->canEdit(...$overrides));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public function editableContentProvider(): array
    {
        return [
            'organizer' => [['contentType' => 'organisateur']],
            'hunt creation' => [[
                'contentType' => 'chasse',
                'businessStatus' => 'revision',
                'validationStatus' => 'creation',
            ]],
            'hunt correction' => [[
                'contentType' => 'chasse',
                'businessStatus' => 'revision',
                'validationStatus' => 'correction',
            ]],
            'riddle' => [[
                'contentType' => 'enigme',
                'businessStatus' => 'revision',
                'validationStatus' => 'creation',
                'systemStatus' => 'bloquee_chasse',
                'hasHunt' => true,
                'huntPublicationStatus' => 'pending',
            ]],
            'disabled hint' => [['contentType' => 'indice', 'systemStatus' => 'desactive']],
            'hint without cached status' => [['contentType' => 'indice']],
        ];
    }

    /**
     * @dataProvider deniedContentProvider
     */
    public function testInvalidContextPreventsFieldEdition(array $overrides): void
    {
        $this->assertFalse($this->canEdit(...$overrides));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public function deniedContentProvider(): array
    {
        return [
            'hidden panel administrator' => [['canViewPanel' => false, 'isAdministrator' => true]],
            'unrelated organizer' => [['contentType' => 'organisateur', 'canModifyContent' => false]],
            'published hunt' => [[
                'contentType' => 'chasse',
                'publicationStatus' => 'publish',
                'businessStatus' => 'revision',
                'validationStatus' => 'creation',
            ]],
            'active hunt' => [[
                'contentType' => 'chasse',
                'businessStatus' => 'en_cours',
                'validationStatus' => 'creation',
            ]],
            'riddle without hunt' => [[
                'contentType' => 'enigme',
                'businessStatus' => 'revision',
                'validationStatus' => 'creation',
                'systemStatus' => 'bloquee_chasse',
            ]],
            'accessible riddle' => [[
                'contentType' => 'enigme',
                'businessStatus' => 'revision',
                'validationStatus' => 'creation',
                'systemStatus' => 'accessible',
                'hasHunt' => true,
                'huntPublicationStatus' => 'pending',
            ]],
            'active hint' => [['contentType' => 'indice', 'systemStatus' => 'accessible']],
            'unknown content' => [['contentType' => 'solution']],
        ];
    }

    private function canEdit(
        bool $canViewPanel = true,
        bool $isAdministrator = false,
        bool $canModifyContent = true,
        string $contentType = 'organisateur',
        string $publicationStatus = 'pending',
        string $validationStatus = '',
        string $businessStatus = '',
        string $systemStatus = '',
        bool $hasHunt = false,
        string $huntPublicationStatus = ''
    ): bool {
        return $this->service->canEdit(
            $canViewPanel,
            $isAdministrator,
            $canModifyContent,
            $contentType,
            $publicationStatus,
            $validationStatus,
            $businessStatus,
            $systemStatus,
            $hasHunt,
            $huntPublicationStatus
        );
    }
}
