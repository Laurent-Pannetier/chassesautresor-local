<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleCreationRequestService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleCreationRequestService.php';

class RiddleCreationRequestServiceTest extends TestCase {
    /**
     * @dataProvider requestErrorProvider
     */
    public function testRequestErrorsFollowSecurityOrder(
        bool $hasValidNonce,
        bool $isLoggedIn,
        bool $hasValidHunt,
        ?string $expected
    ): void {
        $this->assertSame(
            $expected,
            (new RiddleCreationRequestService())->getRequestError($hasValidNonce, $isLoggedIn, $hasValidHunt)
        );
    }

    public function requestErrorProvider(): array {
        return [
            'invalid nonce first' => [false, false, false, 'invalid_nonce'],
            'authentication second' => [true, false, false, 'authentication_required'],
            'invalid hunt third' => [true, true, false, 'invalid_hunt'],
            'valid request' => [true, true, true, null],
        ];
    }
}
