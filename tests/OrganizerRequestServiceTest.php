<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Relationships\OrganizerRequestService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/OrganizerRequestService.php';

class OrganizerRequestServiceTest extends TestCase
{
    private OrganizerRequestService $service;

    protected function setUp(): void
    {
        $this->service = new OrganizerRequestService();
    }

    public function testMissingTokenHasNoPendingRequest(): void
    {
        $this->assertSame(
            ['token' => null, 'expired' => false, 'clear' => false],
            $this->service->getStatus('', 100, 200, 100)
        );
    }

    public function testInvalidRequestDateMustBeCleared(): void
    {
        $this->assertSame(
            ['token' => null, 'expired' => false, 'clear' => true],
            $this->service->getStatus('token', null, 200, 100)
        );
    }

    public function testExpiredRequestMustBeCleared(): void
    {
        $this->assertSame(
            ['token' => null, 'expired' => true, 'clear' => true],
            $this->service->getStatus('token', 100, 201, 100)
        );
    }

    public function testRequestRemainsValidAtExactExpirationTime(): void
    {
        $this->assertSame(
            ['token' => 'token', 'expired' => false, 'expires_at' => 200, 'clear' => false],
            $this->service->getStatus('token', 100, 200, 100)
        );
    }

    public function testNegativeLifetimeExpiresAtRequestTime(): void
    {
        $this->assertSame(
            ['token' => null, 'expired' => true, 'clear' => true],
            $this->service->getStatus('token', 100, 101, -10)
        );
    }
}
