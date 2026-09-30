<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntGenericFieldMutationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntGenericFieldMutationService.php';

final class HuntGenericFieldMutationServiceTest extends TestCase {
    /** @dataProvider allowedFieldProvider */
    public function testPersistsExplicitlyAllowedFields(string $field, $value, $expected): void {
        $updates = [];
        $result = $this->apply($field, $value, $updates);

        $this->assertTrue($result['handled']);
        $this->assertNull($result['error']);
        $this->assertSame([[$field, $expected, 42]], $updates);
    }

    public function allowedFieldProvider(): array {
        return [
            'image' => ['chasse_principale_image', '123', 123],
            'description' => ['chasse_principale_description', '<p>Texte</p>', '<p>Texte</p>'],
            'end mode' => ['chasse_mode_fin', 'manuelle', 'manuelle'],
            'unprefixed hunt field' => ['chasse_infos_cout_points', '5', 5],
        ];
    }

    public function testRejectsAnUnknownFieldWithoutPersistingIt(): void {
        $updates = [];
        $result = $this->apply('champ_injecte', 'valeur', $updates);

        $this->assertFalse($result['handled']);
        $this->assertSame('champ_non_autorise', $result['error']);
        $this->assertSame([], $updates);
    }

    public function testAcceptsAnUnchangedValueWhenAcfReportsFalse(): void {
        $updates = [];
        $result = $this->apply('chasse_mode_fin', 'manuelle', $updates, false, 'manuelle');

        $this->assertTrue($result['handled']);
        $this->assertNull($result['error']);
    }

    public function testReportsAPersistenceFailure(): void {
        $updates = [];
        $result = $this->apply('chasse_mode_fin', 'manuelle', $updates, false, 'automatique');

        $this->assertTrue($result['handled']);
        $this->assertSame('echec_mise_a_jour', $result['error']);
    }

    private function apply(
        string $field,
        $value,
        array &$updates,
        $updateResult = true,
        $storedValue = ''
    ): array {
        return (new HuntGenericFieldMutationService())->apply(
            42,
            $field,
            $value,
            function (string $storedField, $normalizedValue, int $huntId) use (&$updates, $updateResult) {
                $updates[] = [$storedField, $normalizedValue, $huntId];
                return $updateResult;
            },
            static fn (int $huntId, string $storedField, bool $single) => $storedValue,
            static fn ($comparedValue) => $comparedValue
        );
    }
}
