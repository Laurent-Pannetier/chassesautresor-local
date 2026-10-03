<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SingleHuntHomepageTemplateTest extends TestCase
{
    private const THEME_PATH = __DIR__ . '/../wp-content/themes/chassesautresor';

    public function testFrontPageRoutesBothSiteModes(): void
    {
        $template = (string) file_get_contents(self::THEME_PATH . '/front-page.php');

        self::assertStringContainsString('cat_is_single_hunt_mode()', $template);
        self::assertStringContainsString("get_template_part('template-parts/home/platform')", $template);
        self::assertStringContainsString("get_template_part('template-parts/home/single-hunt')", $template);
    }

    public function testSingleHuntHomepageHasNoCatalogControls(): void
    {
        $template = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/home/single-hunt.php'
        );

        self::assertStringContainsString('cat_get_primary_hunt_id()', $template);
        self::assertStringContainsString('cat_is_demo_mode()', $template);
        self::assertStringContainsString('single-hunt-home__facts', $template);
        self::assertStringContainsString('single-hunt-home__story', $template);
        self::assertStringContainsString('single-hunt-home__riddles', $template);
        self::assertStringNotContainsString('home-hunts__toolbar', $template);
        self::assertStringNotContainsString('ca_home_filter_chasse_ids', $template);
    }

    public function testPlatformHomepageKeepsTheExistingCatalog(): void
    {
        $template = (string) file_get_contents(self::THEME_PATH . '/template-parts/home/platform.php');

        self::assertStringContainsString('home-hunts__toolbar', $template);
        self::assertStringContainsString('ca_home_filter_chasse_ids', $template);
    }

    public function testSingleHuntHeroIsImmediatelyVisibleWithoutThePlatformAnimation(): void
    {
        $template = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/headers/front-page-latest-hero.php'
        );

        self::assertStringContainsString("' is-home-hero-visible'", $template);
        self::assertStringContainsString('$single_hunt ? \'false\' : \'true\'', $template);
    }

    public function testSingleHuntNavigationRemovesOrganizerEntrancesAndAddsPlayerLinks(): void
    {
        $navigation = (string) file_get_contents(
            self::THEME_PATH . '/inc/single-hunt-navigation.php'
        );

        self::assertStringContainsString('/devenir-organisateur', $navigation);
        self::assertStringContainsString("['organisateur', 'chasse']", $navigation);
        self::assertStringContainsString('single-hunt-story-link', $navigation);
        self::assertStringContainsString('single-hunt-riddles-link', $navigation);
        self::assertStringContainsString('single-hunt-manage-link', $navigation);
        self::assertStringContainsString('cta_get_primary_hunt_cta', $navigation);
    }

    public function testSingleHuntHomepageGuidesGuestsParticipantsAndOrganizers(): void
    {
        $template = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/home/single-hunt.php'
        );

        self::assertStringContainsString('single-hunt-home__journey', $template);
        self::assertStringContainsString('Créer mon compte', $template);
        self::assertStringContainsString('$isEngaged', $template);
        self::assertStringContainsString('$isOrganizer', $template);
        self::assertStringContainsString('single-hunt-progress', $template);
        self::assertStringContainsString('<progress', $template);
        self::assertStringContainsString('Reprenez là où vous vous êtes arrêté.', $template);
    }
}
