<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\ContentFieldPolicyService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/ContentFieldPolicyService.php';

class ContentFieldPolicyServiceTest extends TestCase
{
    private ContentFieldPolicyService $service;

    protected function setUp(): void
    {
        $this->service = new ContentFieldPolicyService();
    }

    public function testAdministratorCanEditAnyFieldInValidContext(): void
    {
        $this->assertTrue($this->canEdit(
            isAdministrator: true,
            canModifyContent: false
        ));
    }

    public function testUnrestrictedFieldCanBeEditedByContentOwner(): void
    {
        $this->assertTrue($this->canEdit(
            contentType: 'chasse',
            fieldName: 'chasse_infos_description'
        ));
    }

    /**
     * @dataProvider advancedFieldProvider
     */
    public function testRestrictedFieldsFollowAdvancedPermission(array $overrides): void
    {
        $this->assertTrue($this->canEdit(...$overrides));
        $this->assertFalse($this->canEdit(...array_merge($overrides, ['canEditAdvancedFields' => false])));
    }

    /** @return array<string, array{array<string, string>}> */
    public function advancedFieldProvider(): array
    {
        return [
            'hunt title' => [['contentType' => 'chasse', 'fieldName' => 'post_title']],
            'hunt point cost' => [[
                'contentType' => 'chasse',
                'fieldName' => 'caracteristiques.chasse_infos_cout_points',
            ]],
            'riddle title' => [['contentType' => 'enigme', 'fieldName' => 'post_title']],
            'hint field' => [['contentType' => 'indice', 'fieldName' => 'indice_titre']],
        ];
    }

    /**
     * @dataProvider editableOrganizerTitleProvider
     */
    public function testOrganizerTitleCanBeEditedDuringCreation(array $overrides): void
    {
        $this->assertTrue($this->canEdit(...$overrides));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public function editableOrganizerTitleProvider(): array
    {
        return [
            'without hunt' => [[
                'contentType' => 'organisateur',
                'fieldName' => 'post_title',
                'hasOrganizerCreationRole' => true,
            ]],
            'with one creation hunt' => [[
                'contentType' => 'organisateur',
                'fieldName' => 'post_title',
                'hasOrganizerCreationRole' => true,
                'huntCount' => 1,
                'creationHuntCount' => 1,
            ]],
        ];
    }

    /**
     * @dataProvider deniedFieldProvider
     */
    public function testInvalidContextPreventsFieldEdition(array $overrides): void
    {
        $this->assertFalse($this->canEdit(...$overrides));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public function deniedFieldProvider(): array
    {
        return [
            'invalid context administrator' => [['hasValidContext' => false, 'isAdministrator' => true]],
            'unrelated user' => [['canModifyContent' => false]],
            'organizer without creation role' => [[
                'contentType' => 'organisateur',
                'fieldName' => 'post_title',
            ]],
            'published organizer' => [[
                'contentType' => 'organisateur',
                'fieldName' => 'post_title',
                'hasOrganizerCreationRole' => true,
                'publicationStatus' => 'publish',
            ]],
            'organizer with several hunts' => [[
                'contentType' => 'organisateur',
                'fieldName' => 'post_title',
                'hasOrganizerCreationRole' => true,
                'huntCount' => 2,
                'creationHuntCount' => 1,
            ]],
            'organizer with non-creation hunt' => [[
                'contentType' => 'organisateur',
                'fieldName' => 'post_title',
                'hasOrganizerCreationRole' => true,
                'huntCount' => 1,
            ]],
        ];
    }

    private function canEdit(
        bool $hasValidContext = true,
        bool $isAdministrator = false,
        bool $canModifyContent = true,
        string $contentType = 'chasse',
        string $fieldName = 'description',
        bool $canEditAdvancedFields = true,
        string $publicationStatus = 'pending',
        bool $hasOrganizerCreationRole = false,
        int $huntCount = 0,
        int $creationHuntCount = 0
    ): bool {
        return $this->service->canEdit(
            $hasValidContext,
            $isAdministrator,
            $canModifyContent,
            $contentType,
            $fieldName,
            $canEditAdvancedFields,
            $publicationStatus,
            $hasOrganizerCreationRole,
            $huntCount,
            $creationHuntCount
        );
    }
}
