<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddlePrerequisiteService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddlePrerequisiteService.php';

class RiddlePrerequisiteServiceTest extends TestCase
{
    private RiddlePrerequisiteService $service;

    protected function setUp(): void
    {
        $this->service = new RiddlePrerequisiteService();
    }

    public function testManualAndAutomaticRiddlesAreEligible(): void
    {
        $this->assertSame(
            [12, 13],
            $this->service->getEligibleIds(10, [12 => 'manuelle', 13 => 'automatique'])
        );
    }

    public function testCurrentRiddleIsExcluded(): void
    {
        $this->assertSame(
            [12],
            $this->service->getEligibleIds(10, [10 => 'automatique', 12 => 'manuelle'])
        );
    }

    public function testUnsupportedValidationModesAreExcluded(): void
    {
        $this->assertSame(
            [14],
            $this->service->getEligibleIds(10, [11 => 'aucune', 12 => '', 13 => 'manuel', 14 => 'manuelle'])
        );
    }

    public function testIdentifiersAreNormalizedToIntegers(): void
    {
        $this->assertSame([12], $this->service->getEligibleIds(10, ['12' => 'automatique']));
    }

    public function testEmptyCollectionReturnsEmptyList(): void
    {
        $this->assertSame([], $this->service->getEligibleIds(10, []));
    }

    public function testValidConditionUpdateHasNoError(): void
    {
        $this->assertNull($this->service->getConditionUpdateError(true, true, true, [12]));
    }

    /**
     * @dataProvider conditionUpdateErrorProvider
     */
    public function testInvalidConditionUpdateReturnsErrorCode(array $context, string $expectedError): void
    {
        $this->assertSame($expectedError, $this->service->getConditionUpdateError(...$context));
    }

    /** @return array<string, array{array{bool, bool, bool, array<int, int>}, string}> */
    public function conditionUpdateErrorProvider(): array
    {
        return [
            'guest' => [
                [false, true, true, [12]],
                RiddlePrerequisiteService::ERROR_UNAUTHENTICATED,
            ],
            'invalid riddle' => [
                [true, false, true, [12]],
                RiddlePrerequisiteService::ERROR_INVALID_RIDDLE,
            ],
            'unrelated author' => [
                [true, true, false, [12]],
                RiddlePrerequisiteService::ERROR_FORBIDDEN,
            ],
            'empty prerequisites' => [
                [true, true, true, []],
                RiddlePrerequisiteService::ERROR_MISSING_PREREQUISITES,
            ],
            'empty prerequisite values' => [
                [true, true, true, [0]],
                RiddlePrerequisiteService::ERROR_MISSING_PREREQUISITES,
            ],
        ];
    }
}
