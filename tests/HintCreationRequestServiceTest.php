<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintCreationRequestService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintCreationRequestService.php';

final class HintCreationRequestServiceTest extends TestCase {
    private HintCreationRequestService $service;

    protected function setUp(): void {
        $this->service = new HintCreationRequestService();
    }

    public function testHuntTakesPrecedenceOverRiddleTarget(): void {
        $this->assertSame(
            ['id' => 12, 'type' => 'chasse'],
            $this->service->resolveTarget(12, 24)
        );
    }

    public function testRiddleIsUsedWhenHuntIsMissing(): void {
        $this->assertSame(
            ['id' => 24, 'type' => 'enigme'],
            $this->service->resolveTarget(0, 24)
        );
    }

    public function testMissingTargetIsRejected(): void {
        $this->assertNull($this->service->resolveTarget(0, 0));
        $this->assertNull($this->service->resolveTarget(-1, -2));
    }

    public function testRequestErrorsFollowSecurityOrder(): void {
        $target = ['id' => 12, 'type' => 'chasse'];

        $this->assertSame('invalid_nonce', $this->service->getRequestError(false, false, null));
        $this->assertSame('authentication_required', $this->service->getRequestError(true, false, null));
        $this->assertSame('missing_target', $this->service->getRequestError(true, true, null));
        $this->assertNull($this->service->getRequestError(true, true, $target));
    }
}
