<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\OrganizerCreationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/OrganizerCreationService.php';

final class OrganizerCreationServiceTest extends TestCase {
    private OrganizerCreationService $service;

    protected function setUp(): void {
        $this->service = new OrganizerCreationService();
    }

    public function testRejectsInvalidUserAndReturnsExistingOrganizer(): void {
        $never = static function (): void {
            self::fail('Persistence must not be called.');
        };

        $isError = static fn (): bool => false;
        $invalid = $this->service->create(0, 0, 'Title', '', $never, $never, $isError);
        $existing = $this->service->create(7, 42, 'Title', '', $never, $never, $isError);

        $this->assertSame('invalid_user', $invalid['error']);
        $this->assertSame(42, $existing['organizer_id']);
        $this->assertFalse($existing['created']);
    }

    public function testCreatesOrganizerAndInitializesRequiredFields(): void {
        $fields = [];
        $postData = [];

        $result = $this->service->create(
            7,
            0,
            'Nouvel organisateur',
            'organizer@example.com',
            static function (array $data) use (&$postData): int {
                $postData = $data;
                return 84;
            },
            static function (string $field, $value, int $postId) use (&$fields): bool {
                $fields[] = [$field, $value, $postId];
                return true;
            },
            static fn (): bool => false
        );

        $this->assertSame([
            'post_type' => 'organisateur',
            'post_status' => 'pending',
            'post_title' => 'Nouvel organisateur',
            'post_author' => 7,
        ], $postData);
        $this->assertSame([
            ['utilisateurs_associes', ['7'], 84],
            ['logo_organisateur', 3927, 84],
            ['profil_public_email_contact', 'organizer@example.com', 84],
        ], $fields);
        $this->assertSame(['organizer_id' => 84, 'created' => true, 'error' => null], $result);
    }

    public function testReportsPostCreationFailure(): void {
        $result = $this->service->create(
            7,
            0,
            'Title',
            '',
            static fn (): object => (object) ['error' => true],
            static function (): void {
                self::fail('Fields must not be initialized.');
            },
            static fn ($value): bool => is_object($value)
        );

        $this->assertSame('creation_failed', $result['error']);
        $this->assertNull($result['organizer_id']);
    }

    public function testEnsuresMissingAuthorRelationshipOnlyForRegularOrganizerSave(): void {
        $updates = [];
        $update = static function (string $field, $value, int $postId) use (&$updates): void {
            $updates[] = [$field, $value, $postId];
        };

        $this->assertTrue($this->service->ensureAuthorRelationship(84, 'organisateur', false, 7, '', $update));
        $this->assertFalse($this->service->ensureAuthorRelationship(84, 'organisateur', false, 7, ['8'], $update));
        $this->assertFalse($this->service->ensureAuthorRelationship(84, 'organisateur', true, 7, [], $update));
        $this->assertSame([['utilisateurs_associes', ['7'], 84]], $updates);
    }
}
