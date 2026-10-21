<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Teams\TeamActor;
use ChassesAuTresor\Core\Teams\TeamService;
use ChassesAuTresor\Core\Teams\TeamSettings;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/MemoryTeamStore.php';

final class TeamServiceTest extends TestCase
{
    public function testCreateJoinsTheCreatorAndRefusesASecondTeam(): void
    {
        [$service, $store] = $this->service();
        $creator = new TeamActor(4, false);

        $created = $service->create($creator, '  Les  Renards  ', 15);

        self::assertTrue($created->success);
        self::assertSame('created', $created->code);
        self::assertSame($created->teamId, $store->activeTeamId(4));
        $view = $service->describe($creator);
        self::assertSame('Les Renards', $view['team']['name']);
        self::assertSame(15, $view['team']['imageId']);
        self::assertTrue($view['team']['members'][0]['creator']);
        self::assertSame('already_member', $service->create($creator, 'Autre', null)->code);
        self::assertSame('already_member', $service->join($creator, 'ABCD2345')->code);
    }

    public function testNamesAreUniqueIgnoringCaseAndStayFreeAfterDissolution(): void
    {
        [$service] = $this->service();
        $creator = new TeamActor(1, false);
        $created = $service->create($creator, 'Alpha', null);
        self::assertSame('name_taken', $service->create(new TeamActor(2, false), 'alpha', null)->code);

        self::assertSame('left', $service->leave($creator)->code);
        self::assertNull($service->describe($creator)['team']);
        self::assertSame('invalid_code', $service->join(new TeamActor(3, false), 'ABCD2345')->code);
        $recreated = $service->create(new TeamActor(2, false), 'Alpha', null);
        self::assertSame('created', $recreated->code);
        self::assertNotSame($created->teamId, $recreated->teamId);
    }

    public function testJoinNormalizesTheCodeAndRespectsTheCap(): void
    {
        [$service] = $this->service(2);
        $service->create(new TeamActor(1, false), 'Alpha', null);
        $code = $service->describe(new TeamActor(1, false))['team']['code'];

        $typed = strtolower(substr($code, 0, 4) . ' ' . substr($code, 4));
        self::assertSame('joined', $service->join(new TeamActor(2, false), $typed)->code);
        self::assertSame('full', $service->join(new TeamActor(3, false), $code)->code);
        self::assertSame('invalid_name', $service->create(new TeamActor(3, false), 'A', null)->code);
    }

    public function testLoweringTheCapDoesNotRemoveMembers(): void
    {
        [$wide, $store] = $this->service(5);
        $created = $wide->create(new TeamActor(1, false), 'Alpha', null);
        $code = $wide->describe(new TeamActor(1, false))['team']['code'];
        self::assertSame('joined', $wide->join(new TeamActor(2, false), $code)->code);

        $tight = new TeamService(
            $store,
            new TeamSettings(1),
            new QueueInviteCode(['WXYZ2345']),
            static fn (int $id): string => 'J' . $id
        );
        self::assertSame('full', $tight->join(new TeamActor(3, false), $code)->code);
        self::assertCount(2, $store->activeMembers($created->teamId));
    }

    public function testLeaveKeepsTheTeamUntilTheLastMemberAndAllowsANewOne(): void
    {
        [$service, $store] = $this->service();
        $created = $service->create(new TeamActor(1, false), 'Alpha', null);
        $code = $service->describe(new TeamActor(1, false))['team']['code'];
        $service->join(new TeamActor(2, false), $code);

        self::assertSame('left', $service->leave(new TeamActor(2, false))->code);
        self::assertSame(1, $store->activeCount($created->teamId));
        self::assertSame('created', $service->create(new TeamActor(2, false), 'Beta', null)->code);
        self::assertSame('already_member', $service->join(new TeamActor(2, false), $code)->code);
        self::assertSame('joined', $service->join(new TeamActor(3, false), $code)->code);
    }

    public function testExcludedPlayerCannotRejoinThatTeam(): void
    {
        [$service, $store] = $this->service();
        $created = $service->create(new TeamActor(1, false), 'Alpha', null);
        $code = $service->describe(new TeamActor(1, false))['team']['code'];
        $service->join(new TeamActor(2, false), $code);
        $manager = new TeamActor(9, true);

        self::assertSame('forbidden', $service->exclude(new TeamActor(1, false), $created->teamId, 2)->code);
        self::assertSame('forbidden', $service->exclude($manager, $created->teamId, 9)->code);
        self::assertSame('excluded', $service->exclude($manager, $created->teamId, 2)->code);
        self::assertSame('blocked', $service->join(new TeamActor(2, false), $code)->code);
        self::assertSame('created', $service->create(new TeamActor(2, false), 'Beta', null)->code);
        self::assertSame('exclu', $store->membership($created->teamId, 2)['statut']);
        self::assertSame('excluded', $service->exclude($manager, $created->teamId, 1)->code);
        self::assertNotNull($store->team($created->teamId)['dissolved_at']);
        $names = array_column($service->describe($manager)['directory'], 'name');
        self::assertNotContains('Alpha', $names);
        self::assertContains('Beta', $names);
    }

    public function testOnlyTheCreatorAndManagersEditTheProfileAndCode(): void
    {
        $codes = new QueueInviteCode(['ABCD2345', 'EFGH6789']);
        $store = new MemoryTeamStore();
        $service = new TeamService($store, new TeamSettings(5), $codes, static fn (int $id): string => 'J' . $id);
        $created = $service->create(new TeamActor(1, false), 'Alpha', 4);
        $code = 'ABCD2345';
        $service->join(new TeamActor(2, false), $code);

        $refused = $service->updateProfile(new TeamActor(2, false), $created->teamId, 'Non', null, false);
        self::assertSame('forbidden', $refused->code);
        $renamed = $service->updateProfile(new TeamActor(1, false), $created->teamId, 'Renards', null, true);
        self::assertSame('updated', $renamed->code);
        self::assertNull($service->describe(new TeamActor(1, false))['team']['imageId']);
        self::assertSame('regenerated', $service->regenerate(new TeamActor(1, false), $created->teamId)->code);
        self::assertSame('invalid_code', $service->join(new TeamActor(3, false), $code)->code);
        self::assertSame('joined', $service->join(new TeamActor(3, false), 'EFGH6789')->code);

        $service->leave(new TeamActor(1, false));
        $memberEdit = $service->updateProfile(new TeamActor(2, false), $created->teamId, 'Encore', null, false);
        self::assertSame('forbidden', $memberEdit->code);
        $managerEdit = $service->updateProfile(new TeamActor(9, true), $created->teamId, 'Encore', 8, true);
        self::assertSame('updated', $managerEdit->code);
        self::assertSame('Encore', $service->describe(new TeamActor(2, false))['team']['name']);
        self::assertSame([], $service->describe(new TeamActor(2, false))['directory']);
        self::assertNotEmpty($service->describe(new TeamActor(9, true))['directory']);
    }

    public function testAPlayerWhoLeftCanRejoinWhileThereIsRoom(): void
    {
        [$service] = $this->service();
        $service->create(new TeamActor(1, false), 'Alpha', null);
        $code = $service->describe(new TeamActor(1, false))['team']['code'];
        $service->join(new TeamActor(2, false), $code);
        $service->leave(new TeamActor(2, false));

        self::assertSame('joined', $service->join(new TeamActor(2, false), $code)->code);
        self::assertSame(2, $service->describe(new TeamActor(1, false))['team']['count']);
    }

    /** @return array{0: TeamService, 1: MemoryTeamStore} */
    private function service(int $max = 5): array
    {
        $store = new MemoryTeamStore();
        $service = new TeamService(
            $store,
            new TeamSettings($max),
            new QueueInviteCode(['ABCD2345', 'EFGH6789', 'JKLM2345', 'NPQR6789']),
            static fn (int $id): string => 'J' . $id
        );

        return [$service, $store];
    }
}
