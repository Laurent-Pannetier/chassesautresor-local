<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionCreationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionCreationService.php';

class SolutionCreationServiceTest extends TestCase {
    private SolutionCreationService $service;

    protected function setUp(): void {
        $this->service = new SolutionCreationService();
    }

    public function testOnlyHuntsAndRiddlesAreSupportedTargets(): void {
        $this->assertTrue($this->service->isSupportedTargetType('chasse'));
        $this->assertTrue($this->service->isSupportedTargetType('enigme'));
        $this->assertFalse($this->service->isSupportedTargetType('solution'));
        $this->assertFalse($this->service->isSupportedTargetType(''));
    }

    /**
     * @dataProvider creationErrorProvider
     */
    public function testCreationErrorsFollowValidationOrder(array $context, ?string $expected): void {
        $this->assertSame($expected, $this->service->getCreationError(...$context));
    }

    public function creationErrorProvider(): array {
        return [
            'unsupported type' => [[false, false, false, false, false, true], 'type_invalide'],
            'invalid target' => [[true, false, false, false, false, true], 'cible_invalide'],
            'guest' => [[true, true, false, false, false, true], 'non_connecte'],
            'forbidden' => [[true, true, true, false, true, true], 'permission_refusee'],
            'missing hunt' => [[true, true, true, true, false, true], 'permission_refusee'],
            'duplicate' => [[true, true, true, true, true, true], 'existe_deja'],
            'allowed' => [[true, true, true, true, true, false], null],
        ];
    }

    public function testNewSolutionStartsPendingForEndOfHunt(): void {
        $this->assertSame(
            [
                'post_status' => 'pending',
                'availability' => 'fin_chasse',
                'delay_days' => 0,
                'publication_time' => '00:00',
                'system_state' => 'desactive',
            ],
            $this->service->getInitialState('desactive')
        );
    }

    public function testGeneratedTitleIncludesTargetTitle(): void {
        $this->assertSame(
            'Solution | Le trésor',
            $this->service->getGeneratedTitle('Solution | %s', 'Le trésor')
        );
    }
}
