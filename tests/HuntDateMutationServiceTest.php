<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntDateMutationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntDateMutationService.php';

final class HuntDateMutationServiceTest extends TestCase {
    private HuntDateMutationService $service;
    private array $meta;

    protected function setUp(): void {
        $this->service = new HuntDateMutationService();
        $this->meta = [];
    }

    public function testPersistsFiniteScheduleAndDeferredFlag(): void {
        $result = $this->apply('2026-10-01T12:30', '2026-10-03', false, true);

        $this->assertNull($result['error']);
        $this->assertSame([
            'date_debut' => '2026-10-01 12:30:00',
            'date_fin' => '2026-10-03',
            'illimitee' => 0,
        ], $result['data']);
        $this->assertSame(1, $this->meta['chasse_infos_date_debut_differee']);
    }

    public function testUnlimitedSchedulePreservesExistingEndDate(): void {
        $this->meta['chasse_infos_date_fin'] = '2028-01-01';

        $result = $this->apply('2026-10-01 12:30:00', '', true, false);

        $this->assertNull($result['error']);
        $this->assertSame('', $result['data']['date_fin']);
        $this->assertSame('2028-01-01', $this->meta['chasse_infos_date_fin']);
        $this->assertSame(1, $this->meta['chasse_infos_duree_illimitee']);
    }

    public function testRejectsInvalidOrInvertedDatesBeforeWriting(): void {
        $this->assertSame('format_debut_invalide', $this->apply('invalid', '2026-10-03')['error']);
        $this->assertSame('format_fin_invalide', $this->apply('2026-10-01', 'invalid')['error']);
        $this->assertSame(
            'date_fin_avant_debut',
            $this->apply('2026-10-03 12:00:00', '2026-10-03')['error']
        );
        $this->assertSame([], $this->meta);
    }

    public function testAcceptsFalseUpdateWhenStoredValueMatches(): void {
        $result = $this->apply('2026-10-01', '2026-10-03', false, false, false);

        $this->assertNull($result['error']);
    }

    public function testReportsFailedWriteWhenStoredValueDiffers(): void {
        $result = $this->apply('2026-10-01', '2026-10-03', false, false, false, false);

        $this->assertSame('echec_mise_a_jour', $result['error']);
        $this->assertNull($result['data']);
    }

    private function apply(
        string $start,
        string $end,
        bool $unlimited = false,
        bool $deferred = false,
        $updateResult = true,
        bool $persistUpdates = true
    ): array {
        return $this->service->apply(
            42,
            $start,
            $end,
            $unlimited,
            $deferred,
            static function (string $date, array $formats): ?DateTimeImmutable {
                foreach ($formats as $format) {
                    $parsed = DateTimeImmutable::createFromFormat('!' . $format, $date);
                    if ($parsed instanceof DateTimeImmutable) {
                        return $parsed;
                    }
                }

                return null;
            },
            function (string $field, $value) use ($updateResult, $persistUpdates) {
                if ($persistUpdates) {
                    $this->meta[$field] = $value;
                }

                return $updateResult;
            },
            function (int $huntId, string $field, int $value): void {
                $this->meta[$field] = $value;
            },
            fn (int $huntId, string $field) => $this->meta[$field] ?? null
        );
    }
}
