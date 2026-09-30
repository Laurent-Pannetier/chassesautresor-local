<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\OrganizerMutationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/OrganizerMutationService.php';

final class OrganizerMutationServiceTest extends TestCase {
    private OrganizerMutationService $service;

    protected function setUp(): void {
        $this->service = new OrganizerMutationService();
    }

    public function testRejectsShortDescriptionBeforePersistence(): void {
        $callbacks = $this->callbacks();
        $result = $this->service->apply(
            12,
            'parlez_de_vous_presentation',
            '<p>Trop court</p>',
            ...$callbacks
        );

        $this->assertSame('description_too_short', $result['error']);
        $this->assertSame([], $this->updates);
    }

    public function testRejectsFieldsOutsideOrganizerEditorAllowlist(): void {
        $result = $this->service->apply(
            12,
            'utilisateurs_associes',
            '[99]',
            ...$this->callbacks()
        );

        $this->assertSame('field_not_allowed', $result['error']);
        $this->assertSame([], $this->updates);
    }

    public function testMapsAndPersistsSimpleFields(): void {
        $callbacks = $this->callbacks();
        $result = $this->service->apply(12, 'email_contact', 'test@example.com', ...$callbacks);

        $this->assertNull($result['error']);
        $this->assertSame([
            ['field', 'profil_public_email_contact', 'test@example.com', 12],
        ], $this->updates);
    }

    public function testSanitizesPublicLinksAndAcceptsAnUnchangedValue(): void {
        $this->savedFields['liens_publics'] = [
            ['type_de_lien' => 'site_web', 'url_lien' => 'https://example.com'],
        ];
        $callbacks = $this->callbacks(false);
        $value = json_encode([
            ['type_de_lien' => ' site_web ', 'url_lien' => ' https://example.com '],
            ['type_de_lien' => '', 'url_lien' => 'https://ignored.example.com'],
        ]);
        $result = $this->service->apply(12, 'liens_publics', $value, ...$callbacks);

        $this->assertNull($result['error']);
        $this->assertSame($this->savedFields['liens_publics'], $result['value']);
    }

    public function testRejectsInvalidPublicLinksJson(): void {
        $result = $this->service->apply(12, 'liens_publics', '{', ...$this->callbacks());

        $this->assertSame('invalid_format', $result['error']);
        $this->assertSame([], $this->updates);
    }

    public function testUpdatesCurrentAndLegacyBankFields(): void {
        $callbacks = $this->callbacks();
        $result = $this->service->apply(
            12,
            'coordonnees_bancaires',
            '{"iban":" FR76 123 ","bic":" TESTBIC "}',
            ...$callbacks
        );

        $this->assertNull($result['error']);
        $this->assertSame(['iban' => 'FR76 123', 'bic' => 'TESTBIC'], $result['value']);
        $this->assertSame([
            ['field', 'iban', 'FR76 123', 12],
            ['field', 'bic', 'TESTBIC', 12],
            ['field', 'gagnez_de_largent_iban', 'FR76 123', 12],
            ['field', 'gagnez_de_largent_bic', 'TESTBIC', 12],
        ], $this->updates);
    }

    public function testReportsNativeTitleUpdateFailure(): void {
        $callbacks = $this->callbacks();
        $callbacks[3] = static fn (): object => (object) ['error' => true];
        $callbacks[4] = static fn ($value): bool => is_object($value);
        $result = $this->service->apply(12, 'post_title', 'Titre', ...$callbacks);

        $this->assertSame('title_update_failed', $result['error']);
    }

    /** @var array<int, array<int, mixed>> */
    private array $updates = [];

    /** @var array<string, mixed> */
    private array $savedFields = [];

    /** @return array<int, callable> */
    private function callbacks(bool $updateResult = true): array {
        $this->updates = [];

        return [
            static fn (string $value): string => trim($value),
            static fn (string $value): string => trim($value),
            static fn (string $value): string => strip_tags($value),
            static fn (): int => 12,
            static fn (): bool => false,
            function (string $field, $value, int $postId) use ($updateResult): bool {
                $this->updates[] = ['field', $field, $value, $postId];
                return $updateResult;
            },
            fn (string $field) => $this->savedFields[$field] ?? null,
            fn (int $postId, string $field) => $this->savedFields[$field] ?? null,
        ];
    }
}
