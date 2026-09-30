<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleActionPolicyService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleActionPolicyService.php';

class RiddleActionPolicyServiceTest extends TestCase {
    /**
     * @dataProvider errorProvider
     */
    public function testErrorsFollowSecurityOrder(
        bool $isAuthenticated,
        bool $hasValidTarget,
        bool $isAuthorized,
        ?string $expected
    ): void {
        $this->assertSame(
            $expected,
            (new RiddleActionPolicyService())->getError($isAuthenticated, $hasValidTarget, $isAuthorized)
        );
    }

    public function errorProvider(): array {
        return [
            'guest first' => [false, false, false, 'authentication_required'],
            'invalid target second' => [true, false, false, 'invalid_target'],
            'forbidden third' => [true, true, false, 'forbidden'],
            'allowed' => [true, true, true, null],
        ];
    }
}
