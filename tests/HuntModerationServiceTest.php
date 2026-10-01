<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntModerationService;
use PHPUnit\Framework\TestCase;

final class HuntModerationServiceTest extends TestCase
{
    /** @dataProvider actionProvider */
    public function testActionBuildsExpectedTransition(
        string $action,
        string $huntStatus,
        string $validationStatus,
        string $riddleStatus
    ): void {
        $plan = (new HuntModerationService())->plan($action);

        self::assertSame($huntStatus, $plan['hunt_status']);
        self::assertSame($validationStatus, $plan['validation_status']);
        self::assertSame($riddleStatus, $plan['riddle_status']);
    }

    /** @return array<string, array{string,string,string,string}> */
    public function actionProvider(): array
    {
        return [
            'approve' => ['valider', 'publish', 'valide', 'publish'],
            'correction' => ['correction', 'pending', 'correction', 'pending'],
            'ban' => ['bannir', 'draft', 'banni', 'draft'],
            'delete' => ['supprimer', 'trash', 'supprime', 'trash'],
        ];
    }

    public function testRequestRequiresAdministratorAndValidHunt(): void
    {
        $service = new HuntModerationService();

        self::assertSame('access', $service->requestError(false, 12, 'chasse', 'valider'));
        self::assertSame('hunt', $service->requestError(true, 0, 'chasse', 'valider'));
        self::assertSame('hunt', $service->requestError(true, 12, 'post', 'valider'));
        self::assertSame('action', $service->requestError(true, 12, 'chasse', 'unknown'));
        self::assertNull($service->requestError(true, 12, 'chasse', 'valider'));
    }
}
