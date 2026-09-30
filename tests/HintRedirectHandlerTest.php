<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintRedirectHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintRedirectHandler.php';

final class HintRedirectHandlerTest extends TestCase {
    public function testHuntHintRedirectsToLinkedHunt(): void {
        $this->assertSame(
            12,
            HintRedirectHandler::resolveTargetId('chasse', ['ID' => 12], 24)
        );
    }

    public function testRiddleHintRedirectsToLinkedRiddle(): void {
        $this->assertSame(
            24,
            HintRedirectHandler::resolveTargetId('enigme', 12, (object) ['ID' => 24])
        );
    }

    public function testUnknownOrInvalidRelationshipHasNoRedirectTarget(): void {
        $this->assertNull(HintRedirectHandler::resolveTargetId('solution', 12, 24));
        $this->assertNull(HintRedirectHandler::resolveTargetId('chasse', 0, 24));
        $this->assertNull(HintRedirectHandler::resolveTargetId('enigme', 12, null));
    }
}
