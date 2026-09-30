<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionModalPolicyService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionModalPolicyService.php';

class SolutionModalPolicyServiceTest extends TestCase {
    public function testCreationValidationOrder(): void {
        $service = new SolutionModalPolicyService();

        $this->assertSame('non_connecte', $service->getCreationError(false, false, false, false, false));
        $this->assertSame('post_invalide', $service->getCreationError(true, false, false, false, false));
        $this->assertSame('post_invalide', $service->getCreationError(true, true, false, false, false));
        $this->assertSame('acces_refuse', $service->getCreationError(true, true, true, false, false));
        $this->assertSame('contenu_manquant', $service->getCreationError(true, true, true, true, false));
        $this->assertNull($service->getCreationError(true, true, true, true, true));
    }

    public function testEditionValidationOrder(): void {
        $service = new SolutionModalPolicyService();

        $this->assertSame('non_connecte', $service->getEditionError(false, false, false, false, false));
        $this->assertSame('solution_invalide', $service->getEditionError(true, false, false, false, false));
        $this->assertSame('post_invalide', $service->getEditionError(true, true, false, false, false));
        $this->assertSame('acces_refuse', $service->getEditionError(true, true, true, false, false));
        $this->assertSame('contenu_manquant', $service->getEditionError(true, true, true, true, false));
        $this->assertNull($service->getEditionError(true, true, true, true, true));
    }
}
