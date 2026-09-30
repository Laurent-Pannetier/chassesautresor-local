<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntRewardMutationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntRewardMutationService.php';

final class HuntRewardMutationServiceTest extends TestCase {
    private HuntRewardMutationService $service;
    private array $updates;

    protected function setUp(): void {
        $this->service = new HuntRewardMutationService();
        $this->updates = [];
    }

    public function testMapsPrefixedAndUnprefixedRewardFields(): void {
        $title = $this->apply('caracteristiques.chasse_infos_recompense_titre', 'Trésor');
        $text = $this->apply('chasse_infos_recompense_texte', '<p>Description</p>');

        $this->assertTrue($title['handled']);
        $this->assertTrue($text['handled']);
        $this->assertSame([
            ['chasse_infos_recompense_titre', 'Trésor', 42],
            ['chasse_infos_recompense_texte', '<p>Description</p>', 42],
        ], $this->updates);
    }

    public function testValidRewardValueIsPersistedAndRecalculatesStatus(): void {
        $result = $this->apply('chasse_infos_recompense_valeur', '1500.50');

        $this->assertNull($result['error']);
        $this->assertTrue($result['recalculate_status']);
        $this->assertSame(['chasse_infos_recompense_valeur', '1500.50', 42], $this->updates[0]);
    }

    /**
     * @dataProvider invalidValueProvider
     * @param mixed $value
     */
    public function testRejectsInvalidRewardValues($value): void {
        $result = $this->apply('chasse_infos_recompense_valeur', $value);

        $this->assertSame('valeur_invalide', $result['error']);
        $this->assertSame([], $this->updates);
    }

    public function invalidValueProvider(): array {
        return [
            'not numeric' => ['free'],
            'zero' => [0],
            'negative' => [-1],
            'above maximum' => [5000001],
        ];
    }

    public function testUnknownFieldIsNotHandled(): void {
        $result = $this->apply('chasse_principale_description', 'Description');

        $this->assertFalse($result['handled']);
        $this->assertNull($result['error']);
        $this->assertFalse($result['recalculate_status']);
    }

    public function testReportsPersistenceFailure(): void {
        $result = $this->apply('chasse_infos_recompense_titre', 'Trésor', false);

        $this->assertTrue($result['handled']);
        $this->assertSame('echec_mise_a_jour', $result['error']);
    }

    private function apply(string $field, $value, $updateResult = true): array {
        return $this->service->apply(
            42,
            $field,
            $value,
            function (string $storedField, $storedValue, int $huntId) use ($updateResult) {
                if ($updateResult !== false) {
                    $this->updates[] = [$storedField, $storedValue, $huntId];
                }

                return $updateResult;
            }
        );
    }
}
