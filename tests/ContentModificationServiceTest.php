<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\ContentModificationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/ContentModificationService.php';

class ContentModificationServiceTest extends TestCase
{
    private ContentModificationService $service;

    protected function setUp(): void
    {
        $this->service = new ContentModificationService();
    }

    public function testAdministratorCanModifyContentInValidContext(): void
    {
        $this->assertTrue($this->canModify(
            isAdministrator: true,
            contentType: 'inconnu'
        ));
    }

    public function testAssociatedUserCanModifyOrganizer(): void
    {
        $this->assertTrue($this->canModify(
            contentType: 'organisateur',
            isAssociatedUser: true
        ));
    }

    public function testAuthorCanModifyOrganizer(): void
    {
        $this->assertTrue($this->canModify(
            contentType: 'organisateur',
            isAuthor: true
        ));
    }

    /**
     * @dataProvider ownedContentProvider
     */
    public function testUserCanModifyContentThroughOwner(array $overrides): void
    {
        $this->assertTrue($this->canModify(...$overrides));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public function ownedContentProvider(): array
    {
        return [
            'hunt' => [['contentType' => 'chasse', 'hasOwner' => true, 'canModifyOwner' => true]],
            'riddle' => [['contentType' => 'enigme', 'hasOwner' => true, 'canModifyOwner' => true]],
            'hint' => [['contentType' => 'indice', 'hasOwner' => true, 'canModifyOwner' => true]],
        ];
    }

    /**
     * @dataProvider deniedModificationProvider
     */
    public function testInvalidContextPreventsModification(array $overrides): void
    {
        $this->assertFalse($this->canModify(...$overrides));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public function deniedModificationProvider(): array
    {
        return [
            'invalid context administrator' => [['hasValidContext' => false, 'isAdministrator' => true]],
            'unrelated organizer' => [['contentType' => 'organisateur']],
            'content without owner' => [['contentType' => 'chasse', 'canModifyOwner' => true]],
            'unmodifiable owner' => [['contentType' => 'enigme', 'hasOwner' => true]],
            'unsupported content' => [['contentType' => 'solution']],
        ];
    }

    private function canModify(
        bool $hasValidContext = true,
        bool $isAdministrator = false,
        string $contentType = 'organisateur',
        bool $isAssociatedUser = false,
        bool $isAuthor = false,
        bool $hasOwner = false,
        bool $canModifyOwner = false
    ): bool {
        return $this->service->canModify(
            $hasValidContext,
            $isAdministrator,
            $contentType,
            $isAssociatedUser,
            $isAuthor,
            $hasOwner,
            $canModifyOwner
        );
    }
}
