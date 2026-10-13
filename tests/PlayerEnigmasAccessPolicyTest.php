<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Site\PlayerEnigmasAccessPolicy;
use PHPUnit\Framework\TestCase;

final class PlayerEnigmasAccessPolicyTest extends TestCase
{
    private PlayerEnigmasAccessPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new PlayerEnigmasAccessPolicy();
    }

    public function testPlayableHuntIsPublishedValidatedOngoingAndFree(): void
    {
        self::assertTrue($this->policy->isPlayable('publish', 'valide', 'en_cours', 0));
        self::assertTrue($this->policy->isPlayable('publish', 'valide', 'payante', 0));
    }

    public function testUnpublishedUpcomingFinishedOrPaidHuntsStayOutsideTheGate(): void
    {
        self::assertFalse($this->policy->isPlayable('draft', 'valide', 'en_cours', 0));
        self::assertFalse($this->policy->isPlayable('pending', 'valide', 'en_cours', 0));
        self::assertFalse($this->policy->isPlayable('publish', 'creation', 'en_cours', 0));
        self::assertFalse($this->policy->isPlayable('publish', 'valide', 'a_venir', 0));
        self::assertFalse($this->policy->isPlayable('publish', 'valide', 'termine', 0));
        self::assertFalse($this->policy->isPlayable('publish', 'valide', 'en_cours', 5));
    }

    public function testGuestOnThePlayablePrimaryHuntIsSentToLogin(): void
    {
        self::assertSame(
            PlayerEnigmasAccessPolicy::LOGIN,
            $this->policy->destination(true, true, true, false, false, false)
        );
    }

    public function testLoggedInPlayerWhoHasNotJoinedIsSentHome(): void
    {
        self::assertSame(
            PlayerEnigmasAccessPolicy::HOME,
            $this->policy->destination(true, true, true, false, true, false)
        );
    }

    public function testEngagedPlayerAndPrivilegedUsersKeepTheEnigmasPage(): void
    {
        self::assertSame(
            PlayerEnigmasAccessPolicy::ALLOW,
            $this->policy->destination(true, true, true, false, true, true)
        );
        self::assertSame(
            PlayerEnigmasAccessPolicy::ALLOW,
            $this->policy->destination(true, true, true, true, true, false)
        );
    }

    public function testGateStaysOpenOutsideThePlayablePrimaryHunt(): void
    {
        self::assertSame(
            PlayerEnigmasAccessPolicy::ALLOW,
            $this->policy->destination(false, true, true, false, false, false)
        );
        self::assertSame(
            PlayerEnigmasAccessPolicy::ALLOW,
            $this->policy->destination(true, false, true, false, false, false)
        );
        self::assertSame(
            PlayerEnigmasAccessPolicy::ALLOW,
            $this->policy->destination(true, true, false, false, false, false)
        );
    }

    public function testMenuFollowsTheSameDestination(): void
    {
        self::assertTrue($this->policy->showMenu(PlayerEnigmasAccessPolicy::ALLOW));
        self::assertFalse($this->policy->showMenu(PlayerEnigmasAccessPolicy::LOGIN));
        self::assertFalse($this->policy->showMenu(PlayerEnigmasAccessPolicy::HOME));
    }
}
