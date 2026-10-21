<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Teams\TeamActor;
use ChassesAuTresor\Core\Teams\TeamPageRenderer;
use ChassesAuTresor\Core\Teams\TeamService;
use ChassesAuTresor\Core\Teams\TeamSettings;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/MemoryTeamStore.php';

if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}
if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return (string) $text;
    }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = null)
    {
        return $text;
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return (string) $text;
    }
}
if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = null)
    {
        return $text;
    }
}
if (!function_exists('esc_url')) {
    function esc_url($url)
    {
        return (string) $url;
    }
}

final class TeamPageRendererTest extends TestCase
{
    public function testAPlayerWithoutATeamSeesCreateAndJoin(): void
    {
        $html = $this->render(new TeamActor(1, false), '');

        self::assertStringContainsString('Créer une équipe', $html);
        self::assertStringContainsString('Rejoindre une équipe', $html);
        self::assertStringNotContainsString('Exclure', $html);
        self::assertStringNotContainsString('Gestion des équipes', $html);
    }

    public function testAMemberSeesTheCodeAndCannotExclude(): void
    {
        $service = $this->service();
        $service->create(new TeamActor(1, false), 'Alpha', null);
        $html = (new TeamPageRenderer())->render(
            $service->describe(new TeamActor(1, false)),
            'created',
            '/equipe/',
            static fn (int $id): string => '',
            '<svg></svg>'
        );

        self::assertStringContainsString('Équipe créée.', $html);
        self::assertStringContainsString('ABCD2345', $html);
        self::assertStringContainsString('Copier', $html);
        self::assertStringContainsString('Quitter', $html);
        self::assertStringContainsString('Créateur', $html);
        self::assertStringNotContainsString('Créer une équipe', $html);
        self::assertStringNotContainsString('Exclure', $html);
    }

    public function testAManagerSeesTheCapAndExclusion(): void
    {
        $service = $this->service();
        $service->create(new TeamActor(1, false), 'Alpha', null);
        $html = (new TeamPageRenderer())->render(
            $service->describe(new TeamActor(9, true)),
            'nope',
            '/equipe/',
            static fn (int $id): string => '',
            '<svg></svg>'
        );

        self::assertStringContainsString('Gestion des équipes', $html);
        self::assertStringContainsString('Exclure', $html);
        self::assertStringContainsString('name="equipe_max"', $html);
        self::assertStringNotContainsString('nope', $html);
    }

    public function testTheShellOpensTheTeamPageAfterPoints(): void
    {
        $root = dirname(__DIR__);
        $placeholder = (string) file_get_contents(
            $root . '/wp-content/themes/chassesautresor/templates/shell-placeholder.php'
        );
        $navigation = (string) file_get_contents(
            $root . '/wp-content/themes/chassesautresor/inc/myaccount-functions.php'
        );
        $points = strpos($navigation, "'points'");
        $team = strpos($navigation, "'equipe'");

        self::assertStringContainsString("get_template_part('template-parts/shell/equipe')", $placeholder);
        self::assertNotFalse($points);
        self::assertNotFalse($team);
        self::assertLessThan($team, $points);
    }

    private function render(TeamActor $actor, string $notice): string
    {
        return (new TeamPageRenderer())->render(
            $this->service()->describe($actor),
            $notice,
            '/equipe/',
            static fn (int $id): string => '',
            '<svg></svg>'
        );
    }

    private function service(): TeamService
    {
        return new TeamService(
            new MemoryTeamStore(),
            new TeamSettings(5),
            new QueueInviteCode(['ABCD2345']),
            static fn (int $id): string => 'J' . $id
        );
    }
}
