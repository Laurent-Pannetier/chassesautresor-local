<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionAccessService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionAccessService.php';

class SolutionAccessServiceTest extends TestCase
{
    private SolutionAccessService $service;

    protected function setUp(): void
    {
        $this->service = new SolutionAccessService();
    }

    public function testAdministratorCanViewOrphanRiddleSolution(): void
    {
        $this->assertTrue($this->canViewRiddle(isAdministrator: true, hasAssociatedHunt: false));
    }

    public function testRegularUserCannotViewOrphanRiddleSolution(): void
    {
        $this->assertFalse($this->canViewRiddle(hasAssociatedHunt: false));
    }

    public function testOrganizerCanViewRiddleSolution(): void
    {
        $this->assertTrue($this->canViewRiddle(isAssociatedOrganizer: true));
    }

    public function testEngagedUserCanViewRiddleSolutionAfterHuntEnds(): void
    {
        $this->assertTrue($this->canViewRiddle(isHuntFinished: true, isEngagedInRiddle: true));
        $this->assertFalse($this->canViewRiddle(isHuntFinished: false, isEngagedInRiddle: true));
    }

    /**
     * @dataProvider solvedRiddleStatusProvider
     */
    public function testSolvedRiddleStatusAllowsSolutionAccess(string $riddleStatus): void
    {
        $this->assertTrue($this->canViewRiddle(riddleStatus: $riddleStatus));
    }

    /** @return array<string, array{string}> */
    public function solvedRiddleStatusProvider(): array
    {
        return [
            'resolved' => ['resolue'],
            'completed' => ['terminee'],
        ];
    }

    public function testUnresolvedUserCannotViewRiddleSolution(): void
    {
        $this->assertFalse($this->canViewRiddle(riddleStatus: 'en_cours'));
    }

    /**
     * @dataProvider huntAccessProvider
     */
    public function testHuntSolutionAccess(
        bool $isAuthenticated,
        bool $isAdministrator,
        bool $isAssociatedOrganizer,
        bool $isEngagedInHunt,
        bool $expected
    ): void {
        $this->assertSame($expected, $this->service->canViewHuntSolution(
            $isAuthenticated,
            $isAdministrator,
            $isAssociatedOrganizer,
            $isEngagedInHunt
        ));
    }

    /** @return array<string, array{bool, bool, bool, bool, bool}> */
    public function huntAccessProvider(): array
    {
        return [
            'guest is denied' => [false, false, false, false, false],
            'administrator is allowed' => [true, true, false, false, true],
            'organizer is allowed' => [true, false, true, false, true],
            'engaged user is allowed' => [true, false, false, true, true],
            'unrelated user is denied' => [true, false, false, false, false],
        ];
    }

    private function canViewRiddle(
        bool $isAdministrator = false,
        bool $hasAssociatedHunt = true,
        bool $isHuntFinished = false,
        bool $isEngagedInRiddle = false,
        bool $isAssociatedOrganizer = false,
        string $riddleStatus = ''
    ): bool {
        return $this->service->canViewRiddleSolution(
            $isAdministrator,
            $hasAssociatedHunt,
            $isHuntFinished,
            $isEngagedInRiddle,
            $isAssociatedOrganizer,
            $riddleStatus
        );
    }
}
