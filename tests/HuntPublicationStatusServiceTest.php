<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntPublicationStatusService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntPublicationStatusService.php';

class HuntPublicationStatusServiceTest extends TestCase
{
    /**
     * @dataProvider validationStatusProvider
     */
    public function testValidationStatusDeterminesPublicationStatus(
        string $validationStatus,
        string $expectedStatus
    ): void {
        $service = new HuntPublicationStatusService();

        $this->assertSame($expectedStatus, $service->resolve($validationStatus));
    }

    /** @return array<string, array{string, string}> */
    public function validationStatusProvider(): array
    {
        return [
            'validated hunt is public' => ['valide', 'publish'],
            'banned hunt is a draft' => ['banni', 'draft'],
            'hunt being created is pending' => ['creation', 'pending'],
            'hunt awaiting validation is pending' => ['en_attente', 'pending'],
            'hunt needing corrections is pending' => ['correction', 'pending'],
            'unknown validation status is pending' => ['inconnu', 'pending'],
        ];
    }
}
