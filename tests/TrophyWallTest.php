<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Trophies\TrophyDefinition;
use ChassesAuTresor\Core\Trophies\TrophyFieldGroupRegistrar;
use ChassesAuTresor\Core\Trophies\TrophyPageRenderer;
use ChassesAuTresor\Core\Trophies\TrophyWallCatalog;
use ChassesAuTresor\Core\Trophies\TrophyWallRenderer;
use PHPUnit\Framework\TestCase;

if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url)
    {
        return (string) $url;
    }
}

final class TrophyWallTest extends TestCase
{
    public function testHuntLinkIsOptionalOnTheTrophyForm(): void
    {
        $fields = [];
        foreach (TrophyFieldGroupRegistrar::fieldGroup()['fields'] as $field) {
            $fields[$field['name']] = $field;
        }

        self::assertSame('post_object', $fields['trophee_chasse']['type']);
        self::assertSame(['chasse'], $fields['trophee_chasse']['post_type']);
        self::assertFalse($fields['trophee_chasse']['required']);
        self::assertTrue($fields['trophee_chasse']['allow_null']);
        self::assertSame('id', $fields['trophee_chasse']['return_format']);
    }

    public function testOrganizerSeesOnlyTrophiesLinkedToTheirHunts(): void
    {
        $cards = $this->catalog()->cards(
            [
                $this->trophy(1, 0, 'user', 'Plateforme'),
                $this->trophy(2, 10, 'user', 'La sienne'),
                $this->trophy(3, 20, 'user', 'Une autre'),
            ],
            [],
            [],
            [],
            [],
            false,
            [10]
        );

        self::assertSame([2], array_column($cards, 'id'));
        self::assertSame(0, $cards[0]['count']);
        self::assertSame([], $cards[0]['holders']);
    }

    public function testAdministratorAlsoSeesTrophiesWithoutAHunt(): void
    {
        $cards = $this->catalog()->cards(
            [
                $this->trophy(1, 0, 'user', 'Plateforme'),
                $this->trophy(2, 10, 'user', 'Chasse'),
                $this->trophy(4, 10, 'user', 'Inactif', false),
            ],
            [],
            [],
            [],
            [],
            true,
            []
        );

        self::assertSame([1, 2], array_column($cards, 'id'));
    }

    public function testCountFollowsScopeAndSkipsAdministrators(): void
    {
        $awards = [
            $this->award(5, 'user', 11, 11),
            $this->award(5, 'user', 12, 12),
            $this->award(5, 'user', 1, 1),
            $this->award(5, 'team', 8, 11),
            $this->award(6, 'user', 11, 11),
            $this->award(6, 'team', 8, 11),
            $this->award(7, 'team', 8, 11),
            $this->award(7, 'team', 9, 1),
            $this->award(7, 'user', 11, 11),
        ];
        $cards = $this->catalog()->cards(
            [
                $this->trophy(5, 10, 'both', 'Les deux'),
                $this->trophy(6, 10, 'user', 'Joueur'),
                $this->trophy(7, 10, 'team', 'Équipe'),
            ],
            $awards,
            [1],
            [11 => 'Zoé', 12 => 'Alice', 1 => 'Admin'],
            [8 => 'Les Renards', 9 => 'Admin Team'],
            true,
            []
        );
        $byId = [];
        foreach ($cards as $card) {
            $byId[$card['id']] = $card;
        }

        self::assertSame(2, $byId[5]['count']);
        self::assertSame(['Alice', 'Zoé'], $byId[5]['holders']);
        self::assertSame(['Zoé'], $byId[6]['holders']);
        self::assertSame(1, $byId[7]['count']);
        self::assertSame(['Les Renards'], $byId[7]['holders']);
    }

    public function testWallRendersACountOnlyWhenSomeoneHoldsTheTrophy(): void
    {
        $html = (new TrophyWallRenderer())->render([
            [
                'title' => 'Vide',
                'description' => 'Personne.',
                'image_url' => '',
                'count' => 0,
                'holders' => [],
            ],
            [
                'title' => 'Pionnier <b>',
                'description' => '',
                'image_url' => 'https://example.test/t.png',
                'count' => 2,
                'holders' => ['Alice', 'Bob <i>'],
                'context_label' => 'Forêt enchantée',
            ],
        ]);

        self::assertStringContainsString('trophy-card--none', $html);
        self::assertStringContainsString('trophy-card--held', $html);
        self::assertStringContainsString('trophy-card--square', $html);
        self::assertStringContainsString('trophies-wall', $html);
        self::assertSame(1, substr_count($html, 'trophy-card__count'));
        self::assertStringContainsString('>2</summary>', $html);
        self::assertStringContainsString('Alice', $html);
        self::assertStringContainsString(esc_html('Bob <i>'), $html);
        self::assertStringContainsString(esc_html('Pionnier <b>'), $html);
        self::assertStringNotContainsString('>0<', $html);
        self::assertSame(1, substr_count($html, 'trophy-card__banner'));
        self::assertStringContainsString(esc_html('Forêt enchantée'), $html);
        self::assertSame('', (new TrophyWallRenderer())->render([]));
    }

    public function testPlatformTrophyIsLabeledGlobalForTheAdministrator(): void
    {
        require_once dirname(__DIR__)
            . '/wp-content/plugins/chassesautresor-core/src/Trophies/trophy-functions.php';

        self::assertSame('Global', cat_trophy_wall_context_label(0));
        $html = (new TrophyWallRenderer())->render([
            [
                'title' => 'Bienvenue',
                'description' => '',
                'image_url' => '',
                'count' => 0,
                'holders' => [],
                'context_label' => cat_trophy_wall_context_label(0),
            ],
        ]);

        self::assertStringContainsString('trophy-card__banner', $html);
        self::assertStringContainsString('Global', $html);
    }

    public function testPlayerShelfUsesSquareCardsWithoutTheAwardDate(): void
    {
        $html = (new TrophyPageRenderer())->render([
            [
                'title' => 'Bienvenue',
                'description' => 'Participer à la chasse',
                'image_url' => '',
                'awarded_at' => '2026-10-24 12:00:00',
                'recipient_types' => ['user'],
            ],
        ]);

        self::assertStringContainsString('trophies-grid--player', $html);
        self::assertStringContainsString('trophy-card--square', $html);
        self::assertStringContainsString('Bienvenue', $html);
        self::assertStringNotContainsString('Obtenu', $html);
        self::assertStringNotContainsString('2026-10-24', $html);
    }

    public function testTrophyPageKeepsThePlayerShelfAndAddsTheStaffWall(): void
    {
        $template = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/template-parts/shell/trophees.php'
        );

        self::assertStringContainsString('myaccount_user_is_player()', $template);
        self::assertStringContainsString('cat_user_trophies(get_current_user_id())', $template);
        self::assertStringContainsString('cat_trophy_wall((int) get_current_user_id())', $template);
        self::assertStringContainsString('TrophyWallRenderer', $template);
    }

    public function testHolderListCanBeDismissedFromTheTrophyPage(): void
    {
        $shell = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/inc/app-shell.php'
        );
        $script = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/assets/js/trophy-wall.js'
        );

        self::assertStringContainsString("\$shellSlug === 'trophees'", $shell);
        self::assertStringContainsString('assets/js/trophy-wall.js', $shell);
        self::assertStringContainsString("'cta-trophy-wall'", $shell);
        self::assertStringContainsString("event.key !== 'Escape'", $script);
        self::assertStringContainsString("addEventListener('pointerdown'", $script);
        self::assertStringContainsString("addEventListener('click'", $script);
    }

    private function catalog(): TrophyWallCatalog
    {
        return new TrophyWallCatalog();
    }

    private function trophy(
        int $id,
        int $huntId,
        string $scope,
        string $title,
        bool $active = true
    ): TrophyDefinition {
        return new TrophyDefinition(
            $id,
            $title,
            'Texte',
            0,
            $scope,
            'code_event',
            [],
            $active,
            true,
            '',
            '',
            100,
            'both',
            $huntId
        );
    }

    /** @return array<string, int|string> */
    private function award(int $trophyId, string $type, int $recipientId, int $actorId): array
    {
        return [
            'trophy_id' => $trophyId,
            'recipient_type' => $type,
            'recipient_id' => $recipientId,
            'actor_user_id' => $actorId,
        ];
    }
}
