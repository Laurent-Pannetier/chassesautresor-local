<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintFieldMutationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintFieldPolicyService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintStatusService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintFieldMutationService.php';

final class HintFieldMutationServiceTest extends TestCase {
    private HintFieldMutationService $service;
    private array $updatedFields;
    private array $updatedPosts;

    protected function setUp(): void {
        $this->service = new HintFieldMutationService();
        $this->updatedFields = [];
        $this->updatedPosts = [];
    }

    public function testRejectsUnknownFieldsWithoutPersistingThem(): void {
        $result = $this->apply('private_field', 'value');

        $this->assertSame('champ_inconnu', $result['error']);
        $this->assertSame([], $this->updatedFields);
        $this->assertSame([], $this->updatedPosts);
    }

    public function testNormalizesEditableValuesAndRequestsCacheRefresh(): void {
        $availability = $this->apply('indice_disponibilite', 'unexpected');
        $riddles = $this->apply('indice_enigme_linked', '7,2,7,0');
        $content = $this->apply('indice_contenu', '<script>x</script><p>Indice</p>');

        $this->assertNull($availability['error']);
        $this->assertTrue($availability['refresh_cache']);
        $this->assertSame('immediate', $this->updatedFields[0][1]);
        $this->assertSame([7, 2], $this->updatedFields[1][1]);
        $this->assertSame('<p>Indice</p>', $this->updatedFields[2][1]);
    }

    public function testFormatsValidDatesAndRejectsInvalidDates(): void {
        $valid = $this->apply('indice_date_disponibilite', '2026-10-05T18:30');
        $invalid = $this->apply('indice_date_disponibilite', 'not-a-date');

        $this->assertNull($valid['error']);
        $this->assertSame('2026-10-05 18:30:00', $this->updatedFields[0][1]);
        $this->assertSame('format_date_invalide', $invalid['error']);
        $this->assertCount(1, $this->updatedFields);
    }

    public function testUpdatesNativeTitleWithoutRefreshingHintCache(): void {
        $result = $this->apply('post_title', '  New title  ');

        $this->assertNull($result['error']);
        $this->assertFalse($result['refresh_cache']);
        $this->assertSame([['ID' => 42, 'post_title' => 'New title']], $this->updatedPosts);
    }

    public function testReportsPersistenceFailures(): void {
        $result = $this->apply('indice_cout_points', 10, false);

        $this->assertSame('echec_mise_a_jour', $result['error']);
        $this->assertFalse($result['refresh_cache']);
    }

    /**
     * @param mixed $value
     * @return array{error:?string,refresh_cache:bool}
     */
    private function apply(string $field, $value, $fieldResult = true): array {
        return $this->service->apply(
            42,
            $field,
            $value,
            static fn (string $text): string => trim($text),
            static fn (string $html): string => preg_replace('/<script.*?<\/script>/', '', $html),
            static function (string $date): ?DateTimeImmutable {
                $parsed = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $date);

                return $parsed ?: null;
            },
            function (array $postData) {
                $this->updatedPosts[] = $postData;

                return true;
            },
            function (string $name, $normalized, int $hintId) use ($fieldResult) {
                if ($fieldResult !== false) {
                    $this->updatedFields[] = [$name, $normalized, $hintId];
                }

                return $fieldResult;
            },
            static fn ($result): bool => $result instanceof RuntimeException
        );
    }
}
