<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Auth\SocialAuthAccountPlan;
use ChassesAuTresor\Core\Auth\SocialAuthEndpoints;
use ChassesAuTresor\Core\Auth\SocialAuthIdentity;
use ChassesAuTresor\Core\Auth\SocialAuthRedirect;
use ChassesAuTresor\Core\Auth\SocialAuthSettings;
use ChassesAuTresor\Core\Auth\SocialAuthUsername;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Auth/SocialAuthAccountPlan.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Auth/SocialAuthEndpoints.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Auth/SocialAuthIdentity.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Auth/SocialAuthRedirect.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Auth/SocialAuthSettings.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Auth/SocialAuthUsername.php';

final class SocialAuthTest extends TestCase
{
    public function testKnownProviderLogsInAndAForeignSessionDoesNotTakeOver(): void
    {
        $plan = new SocialAuthAccountPlan();

        $login = $plan->decide('42', 'a@exemple.fr', true, 7, 7, 0);
        self::assertSame(SocialAuthAccountPlan::LOGIN, $login['decision']);
        self::assertSame(7, $login['user_id']);

        $conflict = $plan->decide('42', 'a@exemple.fr', true, 7, 7, 9);
        self::assertSame(SocialAuthAccountPlan::REFUSE, $conflict['decision']);
        self::assertSame('session_conflict', $conflict['reason']);
    }

    public function testVerifiedEmailMatchAsksForTheExistingPassword(): void
    {
        $plan = new SocialAuthAccountPlan();
        $decision = $plan->decide('42', 'a@exemple.fr', true, null, 4, 0);

        self::assertSame(SocialAuthAccountPlan::LINK_PASSWORD, $decision['decision']);
        self::assertSame(4, $decision['user_id']);
    }

    public function testUnverifiedOrMissingEmailDoesNotCreateASecondAccount(): void
    {
        $plan = new SocialAuthAccountPlan();

        $unverified = $plan->decide('42', 'a@exemple.fr', false, null, 4, 0);
        self::assertSame('email_unverified', $unverified['reason']);

        $missing = $plan->decide('42', '', false, null, null, 0);
        self::assertSame('email_missing', $missing['reason']);

        $created = $plan->decide('42', 'neuf@exemple.fr', true, null, null, 0);
        self::assertSame(SocialAuthAccountPlan::CREATE, $created['decision']);
    }

    public function testOpenSessionLinksTheProviderWithoutAPassword(): void
    {
        $plan = new SocialAuthAccountPlan();
        $decision = $plan->decide('42', '', false, null, null, 3);

        self::assertSame(SocialAuthAccountPlan::LINK_SESSION, $decision['decision']);
        self::assertSame(3, $decision['user_id']);
    }

    public function testSocialOnlyPasswordResetWaitsForConfirmation(): void
    {
        $plan = new SocialAuthAccountPlan();

        self::assertSame('explain', $plan->passwordReset(true, false));
        self::assertSame('send', $plan->passwordReset(true, true));
        self::assertSame('send', $plan->passwordReset(false, false));
    }

    public function testDiscordAndGoogleProfilesKeepTheStableIdAndVerifiedEmail(): void
    {
        $identity = new SocialAuthIdentity();
        $discord = $identity->fromDiscord([
            'id' => '9988',
            'username' => 'fletcher',
            'global_name' => 'Fletcher',
            'email' => 'Fletcher@Exemple.fr',
            'verified' => true,
        ]);
        $google = $identity->fromGoogle([
            'sub' => 'google-sub',
            'email' => 'not-an-email',
            'email_verified' => 'true',
            'name' => 'Camille',
            'given_name' => 'Camille',
        ]);

        self::assertSame('9988', $discord['id']);
        self::assertSame('fletcher@exemple.fr', $discord['email']);
        self::assertTrue($discord['email_verified']);
        self::assertSame('Fletcher', $discord['display_name']);
        self::assertSame('', $google['email']);
        self::assertFalse($google['email_verified']);
        self::assertSame('google-sub', $google['id']);
    }

    public function testUsernameAvoidsCollisionsAndDropsSpaces(): void
    {
        $taken = ['fletcher'];
        $names = new SocialAuthUsername();
        $login = $names->propose(
            'discord',
            '99',
            'Fletcher',
            static fn (string $candidate): bool => in_array($candidate, $taken, true)
        );

        self::assertSame('fletcher2', $login);
        self::assertSame('elodie', $names->propose('discord', '1', 'Élodie', static fn (): bool => false));
    }

    public function testRedirectRejectsOtherHostsAndTheLoginScreenItself(): void
    {
        $redirects = new SocialAuthRedirect();
        $home = 'https://chassesautresor.test/';

        self::assertSame($home, $redirects->accept('https://evil.test/piege', $home));
        self::assertSame($home, $redirects->accept('//evil.test', $home));
        self::assertSame($home, $redirects->accept('javascript:alert(1)', $home));
        self::assertSame(
            'https://chassesautresor.test/chasse/lyon/',
            $redirects->accept('https://chassesautresor.test/chasse/lyon/', $home)
        );
        self::assertSame($home, $redirects->accept('https://chassesautresor.test/connexion/', $home));
        self::assertSame($home, $redirects->accept('https://chassesautresor.test/wp-login.php', $home));
        self::assertSame(
            'https://chassesautresor.test/equipe/',
            $redirects->accept('/equipe/', $home)
        );
    }

    public function testAuthorizeUrlsStayOnTheProviderAndOmitTheSecret(): void
    {
        $endpoints = new SocialAuthEndpoints();
        $discord = $endpoints->authorizeUrl('discord', '123', 'https://site.test/connexion/', 'abc');
        $google = $endpoints->authorizeUrl(
            'google',
            'client.apps.googleusercontent.com',
            'https://site.test/connexion/',
            'abc'
        );

        self::assertTrue($endpoints->isProviderAuthorizeUrl($discord));
        self::assertTrue($endpoints->isProviderAuthorizeUrl($google));
        self::assertStringNotContainsString('secret', $discord);
        self::assertStringContainsString('scope=identify%20email', $discord);
        self::assertStringContainsString('scope=openid%20email%20profile', $google);
        self::assertFalse($endpoints->isProviderAuthorizeUrl('https://evil.test/discord.com/api/oauth2/authorize?x=1'));
    }

    public function testEmptySecretKeepsTheStoredOneAndAClientIdIsStripped(): void
    {
        $settings = new SocialAuthSettings();
        $clean = $settings->sanitize(
            [
                'discord_client_id' => ' 123 456 ',
                'discord_client_secret' => '',
                'google_client_id' => 'abc.apps.googleusercontent.com',
                'google_client_secret' => 'GOCSPX-nouveau',
            ],
            [
                'discord_client_secret' => 'ancien-secret',
                'google_client_secret' => 'ancien-google',
            ]
        );

        self::assertSame('123456', $clean['discord_client_id']);
        self::assertSame('ancien-secret', $clean['discord_client_secret']);
        self::assertSame('GOCSPX-nouveau', $clean['google_client_secret']);
        self::assertTrue($settings->ready($clean, 'discord'));
        self::assertFalse($settings->ready(['discord_client_id' => '1'], 'discord'));
        self::assertSame('secret', $settings->gap(['discord_client_id' => '1'], 'discord'));
        self::assertSame('id', $settings->gap(['google_client_secret' => 'secret'], 'google'));
        self::assertSame('inactif', $settings->gap([], 'discord'));
        self::assertSame('', $settings->gap($clean, 'discord'));
    }

    public function testSettingsPagePointsToTheProviderPortals(): void
    {
        $page = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/plugins/chassesautresor-core/src/Auth/SocialAuthSettingsPage.php'
        );

        self::assertStringContainsString('https://discord.com/developers/applications', $page);
        self::assertStringContainsString('https://console.cloud.google.com/apis/credentials', $page);
    }
}
