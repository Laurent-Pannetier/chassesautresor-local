<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Users\AccountDashboardDataService;
use PHPUnit\Framework\TestCase;

final class AccountDashboardDataServiceTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBuildsEngagedHuntsPaginationForPlayers(): void {
        function ca_get_user_engaged_hunt_ids(int $userId): array {
            return $userId === 9 ? [10, 20, 30] : [];
        }
        function ca_prepare_engaged_hunts_pagination(array $ids, int $page, int $perPage): array {
            return [
                'ids' => array_slice($ids, ($page - 1) * $perPage, $perPage),
                'page' => $page,
                'total_pages' => (int) ceil(count($ids) / $perPage),
                'total_items' => count($ids),
            ];
        }

        $service = new AccountDashboardDataService((object) []);
        $context = $service->engagedHunts((object) [
            'ID' => 9,
            'roles' => ['customer'],
        ], 2, 2);

        self::assertTrue($context['allowed']);
        self::assertSame([30], $context['pagination']['ids']);
        self::assertSame(2, $context['pagination']['page']);
        self::assertSame(3, $context['pagination']['total_items']);
    }

    public function testRejectsAdministratorsEvenWithAPlayerRole(): void {
        $service = new AccountDashboardDataService((object) []);
        $context = $service->engagedHunts((object) [
            'ID' => 1,
            'roles' => ['administrator', 'customer'],
        ], 1, 6);

        self::assertFalse($context['allowed']);
        self::assertSame([], $context['pagination']['ids']);
    }
}
