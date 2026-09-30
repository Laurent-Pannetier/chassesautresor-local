<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleCreationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleCreationService.php';

class RiddleCreationServiceTest extends TestCase {
    /**
     * @dataProvider creationErrorProvider
     */
    public function testCreationErrorsFollowValidationOrder(
        bool $hasValidHunt,
        bool $hasValidUser,
        bool $hasOrganizer,
        ?string $expected
    ): void {
        $this->assertSame(
            $expected,
            (new RiddleCreationService())->getCreationError($hasValidHunt, $hasValidUser, $hasOrganizer)
        );
    }

    public function creationErrorProvider(): array {
        return [
            'invalid hunt first' => [false, false, false, 'invalid_hunt'],
            'invalid user second' => [true, false, false, 'invalid_user'],
            'missing organizer third' => [true, true, false, 'missing_organizer'],
            'valid creation' => [true, true, true, null],
        ];
    }
}
