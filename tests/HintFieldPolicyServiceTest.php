<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintFieldPolicyService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintFieldPolicyService.php';

class HintFieldPolicyServiceTest extends TestCase
{
    private HintFieldPolicyService $service;

    protected function setUp(): void
    {
        $this->service = new HintFieldPolicyService();
    }

    public function testOnlyKnownAcfFieldsAreEditable(): void
    {
        $this->assertTrue($this->service->isEditable('indice_contenu'));
        $this->assertTrue($this->service->isEditable('indice_cout_points'));
        $this->assertFalse($this->service->isEditable('post_status'));
        $this->assertFalse($this->service->isEditable('unknown'));
    }

    public function testOnlyAvailabilityContentAndMediaRefreshCache(): void
    {
        $this->assertTrue($this->service->requiresCacheRefresh('indice_image'));
        $this->assertTrue($this->service->requiresCacheRefresh('indice_date_disponibilite'));
        $this->assertFalse($this->service->requiresCacheRefresh('indice_cout_points'));
        $this->assertFalse($this->service->requiresCacheRefresh('indice_enigme_linked'));
    }

    public function testTargetTypeDefaultsToHunt(): void
    {
        $this->assertSame('enigme', $this->service->normalizeTargetType('enigme'));
        $this->assertSame('chasse', $this->service->normalizeTargetType('invalid'));
    }

    public function testRiddleIdsArePositiveAndUnique(): void
    {
        $this->assertSame([12, 24], $this->service->normalizeRiddleIds('12,0,-1,24,12,invalid'));
    }
}
