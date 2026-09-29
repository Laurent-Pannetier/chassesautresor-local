<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\ContentPanelAccessService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/ContentPanelAccessService.php';

class ContentPanelAccessServiceTest extends TestCase
{
    private ContentPanelAccessService $service;

    protected function setUp(): void
    {
        $this->service = new ContentPanelAccessService();
    }

    public function testAuthenticatedAdministratorCanViewAnyPanel(): void
    {
        $this->assertTrue($this->canView(
            isAdministrator: true,
            isOrganizer: false,
            canModifyContent: false,
            contentType: 'inconnu',
            publicationStatus: 'draft'
        ));
    }

    /**
     * @dataProvider visibleOrganizerPanelProvider
     */
    public function testOrganizerCanViewSupportedPanel(array $overrides): void
    {
        $this->assertTrue($this->canView(...$overrides));
    }

    /** @return array<string, array{array<string, string>}> */
    public function visibleOrganizerPanelProvider(): array
    {
        return [
            'organizer' => [['contentType' => 'organisateur']],
            'hunt' => [['contentType' => 'chasse', 'validationStatus' => 'creation']],
            'riddle' => [['contentType' => 'enigme', 'systemStatus' => 'accessible']],
            'hint' => [['contentType' => 'indice']],
        ];
    }

    /**
     * @dataProvider deniedPanelProvider
     */
    public function testInvalidContextPreventsPanelAccess(array $overrides): void
    {
        $this->assertFalse($this->canView(...$overrides));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public function deniedPanelProvider(): array
    {
        return [
            'guest administrator' => [['isAuthenticated' => false, 'isAdministrator' => true]],
            'non organizer' => [['isOrganizer' => false]],
            'unrelated organizer' => [['canModifyContent' => false]],
            'draft content' => [['publicationStatus' => 'draft']],
            'banned hunt' => [['contentType' => 'chasse', 'validationStatus' => 'banni']],
            'invalid riddle cache' => [['contentType' => 'enigme', 'systemStatus' => 'cache_invalide']],
            'unsupported type' => [['contentType' => 'solution']],
        ];
    }

    private function canView(
        bool $isAuthenticated = true,
        bool $isAdministrator = false,
        bool $isOrganizer = true,
        bool $canModifyContent = true,
        string $contentType = 'organisateur',
        string $publicationStatus = 'pending',
        string $validationStatus = '',
        string $systemStatus = ''
    ): bool {
        return $this->service->canView(
            $isAuthenticated,
            $isAdministrator,
            $isOrganizer,
            $canModifyContent,
            $contentType,
            $publicationStatus,
            $validationStatus,
            $systemStatus
        );
    }
}
