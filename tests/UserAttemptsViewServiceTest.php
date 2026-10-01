<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\UserAttemptStatisticsRepository;
use ChassesAuTresor\Core\Progress\UserAttemptStatisticsService;
use ChassesAuTresor\Core\Progress\UserAttemptsViewService;
use PHPUnit\Framework\TestCase;

final class UserAttemptsViewServiceTest extends TestCase {
    public function testBuildsTheSharedAttemptsViewModel(): void
    {
        $repository = $this->createMock(UserAttemptStatisticsRepository::class);
        $repository->method('summarize')->with(7)->willReturn([
            'pending' => 2,
            'total' => 5,
            'success' => 3,
        ]);
        $repository->method('countForUser')->with(7, 'pirate')->willReturn(12);
        $repository->method('findForUser')->with(7, 'pirate', 10, 10)->willReturn([(object) ['id' => 42]]);

        $view = (new UserAttemptsViewService(new UserAttemptStatisticsService($repository)))
            ->build(7, 2, 10, ' pirate ');

        self::assertSame(2, $view['pending']);
        self::assertSame(5, $view['total']);
        self::assertSame(3, $view['success']);
        self::assertSame('pirate', $view['search_term']);
        self::assertSame(2, $view['page']);
        self::assertSame(2, $view['pages']);
        self::assertSame(12, $view['filtered_total']);
        self::assertSame(42, $view['tentatives'][0]->id);
        self::assertSame('Aucune tentative ne correspond à votre recherche.', $view['no_results_message']);
    }
}
