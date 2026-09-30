<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntFieldMutationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntFieldMutationService.php';

final class HuntFieldMutationServiceTest extends TestCase {
    private HuntFieldMutationService $service;
    private array $updates;

    protected function setUp(): void {
        $this->service = new HuntFieldMutationService();
        $this->updates = [];
    }

    public function testNormalizesStartDateAndRequestsStatusRecalculation(): void {
        $result = $this->apply('caracteristiques.chasse_infos_date_debut', '2026-10-01T12:30');

        $this->assertNull($result['error']);
        $this->assertTrue($result['recalculate_status']);
        $this->assertSame(['chasse_infos_date_debut', '2026-10-01 12:30:00', 42], $this->updates[0]);
    }

    /** @dataProvider unprefixedFieldProvider */
    public function testHandlesUnprefixedFieldsSentByTheEditor(
        string $field,
        $value,
        string $storedField,
        $storedValue,
        bool $recalculateStatus
    ): void {
        $result = $this->apply($field, $value);

        $this->assertTrue($result['handled']);
        $this->assertNull($result['error']);
        $this->assertSame($recalculateStatus, $result['recalculate_status']);
        $this->assertSame([$storedField, $storedValue, 42], $this->updates[0]);
    }

    public function unprefixedFieldProvider(): array {
        return [
            'start date' => [
                'chasse_infos_date_debut',
                '2026-10-01T12:30',
                'chasse_infos_date_debut',
                '2026-10-01 12:30:00',
                true,
            ],
            'end date' => [
                'chasse_infos_date_fin',
                '2027-10-01',
                'chasse_infos_date_fin',
                '2027-10-01',
                true,
            ],
            'unlimited duration' => [
                'chasse_infos_duree_illimitee',
                '1',
                'chasse_infos_duree_illimitee',
                1,
                true,
            ],
            'points cost' => [
                'chasse_infos_cout_points',
                '25',
                'chasse_infos_cout_points',
                25,
                true,
            ],
            'winner limit' => [
                'chasse_infos_nb_max_gagants',
                '3',
                'chasse_infos_nb_max_gagants',
                3,
                false,
            ],
        ];
    }

    public function testRejectsInvalidDatesWithoutWriting(): void {
        $start = $this->apply('caracteristiques.chasse_infos_date_debut', 'invalid');
        $end = $this->apply('caracteristiques.chasse_infos_date_fin', '01/10/2026');
        $discovery = $this->apply('chasse_cache_date_decouverte', 'invalid');

        $this->assertSame('format_date_invalide', $start['error']);
        $this->assertSame('format_date_invalide', $end['error']);
        $this->assertSame('format_date_invalide', $discovery['error']);
        $this->assertSame([], $this->updates);
    }

    public function testNormalizesNumericAndValidationFields(): void {
        $this->apply('caracteristiques.chasse_infos_duree_illimitee', '1');
        $this->apply('caracteristiques.chasse_infos_cout_points', '25');
        $this->apply('caracteristiques.chasse_infos_nb_max_gagants', '3');
        $validation = $this->apply('champs_caches.chasse_cache_statut_validation', ' validé ');

        $this->assertSame([
            ['chasse_infos_duree_illimitee', 1, 42],
            ['chasse_infos_cout_points', 25, 42],
            ['chasse_infos_nb_max_gagants', 3, 42],
            ['chasse_cache_statut_validation', 'validé', 42],
        ], $this->updates);
        $this->assertTrue($validation['recalculate_status']);
    }

    public function testUnknownFieldIsNotHandled(): void {
        $result = $this->apply('chasse_principale_description', 'Description');

        $this->assertFalse($result['handled']);
        $this->assertNull($result['error']);
    }

    public function testReportsPersistenceFailure(): void {
        $result = $this->apply('champs_caches.chasse_cache_gagnants', 'Alice', false);

        $this->assertTrue($result['handled']);
        $this->assertSame('echec_mise_a_jour', $result['error']);
    }

    private function apply(string $field, $value, $updateResult = true): array {
        return $this->service->apply(
            42,
            $field,
            $value,
            static function (string $date, array $formats): ?DateTimeImmutable {
                foreach ($formats as $format) {
                    $parsed = DateTimeImmutable::createFromFormat('!' . $format, $date);
                    if ($parsed instanceof DateTimeImmutable) {
                        return $parsed;
                    }
                }

                return null;
            },
            static fn (string $text): string => trim($text),
            function (string $storedField, $storedValue, int $huntId) use ($updateResult) {
                if ($updateResult !== false) {
                    $this->updates[] = [$storedField, $storedValue, $huntId];
                }

                return $updateResult;
            }
        );
    }
}
