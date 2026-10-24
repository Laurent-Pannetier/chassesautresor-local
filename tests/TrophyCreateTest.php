<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Trophies\CodeEventTrophyTrigger;
use ChassesAuTresor\Core\Trophies\TrophyCreateFormRenderer;
use ChassesAuTresor\Core\Trophies\TrophyCreateRequestHandler;
use ChassesAuTresor\Core\Trophies\TrophyCreationService;
use ChassesAuTresor\Core\Trophies\TrophyDraft;
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
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = null)
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

final class TrophyCreateTest extends TestCase
{
    public function testDraftKeepsTheShortFormAndRejectsABrokenKey(): void
    {
        $draft = TrophyDraft::from([
            'title' => '  <b>Bienvenue</b>  ',
            'description' => "Premier\npas",
            'hunt_id' => '4',
            'scope' => 'nope',
            'event_key' => 'bienvenue',
        ], 9);

        self::assertNotNull($draft);
        self::assertSame('Bienvenue', $draft->title);
        self::assertSame("Premier\npas", $draft->description);
        self::assertSame(4, $draft->huntId);
        self::assertSame('user', $draft->scope);
        self::assertSame('bienvenue', $draft->eventKey);
        self::assertSame(9, $draft->imageId);
        self::assertNull(TrophyDraft::from(['title' => 'Nom', 'event_key' => 'clé avec espace']));
        self::assertNotNull(TrophyDraft::from(['title' => 'Nom', 'event_key' => 'account.created']));
    }

    public function testCreatePublishesAnActiveTrophyWithNotificationDefaults(): void
    {
        $inserted = [];
        $fields = [];
        $draft = TrophyDraft::from([
            'title' => 'Pionnier',
            'description' => 'Le premier.',
            'hunt_id' => 0,
            'scope' => 'team',
            'event_key' => 'bienvenue',
        ]);
        $id = (new TrophyCreationService())->create(
            $draft,
            7,
            static function (array $post) use (&$inserted) {
                $inserted = $post;

                return 15;
            },
            static function (string $key, $value) use (&$fields): void {
                $fields[$key] = $value;
            }
        );

        self::assertSame(15, $id);
        self::assertSame('trophee', $inserted['post_type']);
        self::assertSame('publish', $inserted['post_status']);
        self::assertSame('Pionnier', $inserted['post_title']);
        self::assertSame(1, $fields['trophee_active']);
        self::assertSame('team', $fields['trophee_scope']);
        self::assertSame(CodeEventTrophyTrigger::TYPE, $fields['trophee_trigger_type']);
        self::assertSame('bienvenue', $fields[CodeEventTrophyTrigger::FIELD]);
        self::assertSame('both', $fields['trophee_notification_channel']);
        self::assertSame('', $fields['trophee_notification_title']);
        self::assertArrayNotHasKey('trophee_chasse', $fields);
        self::assertArrayNotHasKey('trophee_image', $fields);
    }

    public function testProcessStoresTheHuntAndSkipsAForbiddenOrInvalidRequest(): void
    {
        $inserts = 0;
        $fields = [];
        $insert = static function () use (&$inserts) {
            $inserts++;

            return 21;
        };
        $update = static function (string $key, $value) use (&$fields): void {
            $fields[$key] = $value;
        };
        $post = [
            'trophee_titre' => 'Explorateur',
            'trophee_texte' => 'Une forêt.',
            'trophee_chasse' => 8,
            'trophee_attribution' => 'both',
            'trophee_cle' => 'foret',
        ];

        self::assertSame('forbidden', TrophyCreateRequestHandler::process(
            $post,
            ['id' => null, 'error' => false],
            false,
            true,
            static fn (): bool => true,
            $insert,
            $update,
            3
        ));
        self::assertSame(0, $inserts);
        self::assertSame('image', TrophyCreateRequestHandler::process(
            $post,
            ['id' => null, 'error' => true],
            true,
            true,
            static fn (): bool => true,
            $insert,
            $update,
            3
        ));
        self::assertSame('invalid', TrophyCreateRequestHandler::process(
            $post,
            ['id' => 4, 'error' => false],
            true,
            true,
            static fn (): bool => false,
            $insert,
            $update,
            3
        ));
        self::assertSame('ok', TrophyCreateRequestHandler::process(
            $post,
            ['id' => 4, 'error' => false],
            true,
            true,
            static fn (): bool => true,
            $insert,
            $update,
            3
        ));
        self::assertSame(1, $inserts);
        self::assertSame(8, $fields['trophee_chasse']);
        self::assertSame(4, $fields['trophee_image']);
        self::assertSame('both', $fields['trophee_scope']);
    }

    public function testFormOffersGlobalAndTheWallLinksTheWordPressScreen(): void
    {
        $html = (new TrophyCreateFormRenderer())->render(
            'https://example.test/wp-admin/admin-post.php',
            '<input type="hidden" name="cat_trophy_nonce" value="abc">',
            [['id' => 8, 'title' => 'Forêt <i>']],
            true,
            'invalid'
        );

        self::assertStringContainsString('trophy-create" open', $html);
        self::assertStringContainsString('value="cat_trophy_create"', $html);
        self::assertStringContainsString('cat_trophy_nonce', $html);
        self::assertStringContainsString('>' . esc_html('Global') . '<', $html);
        self::assertStringContainsString(esc_html('Forêt <i>'), $html);
        self::assertStringContainsString('trophy-create__notice', $html);
        self::assertStringContainsString('Ajouter', $html);

        $card = (new TrophyWallRenderer())->render([
            [
                'title' => 'Bienvenue',
                'description' => '',
                'image_url' => '',
                'count' => 0,
                'holders' => [],
                'edit_url' => 'https://example.test/wp-admin/post.php?post=3&action=edit',
            ],
        ]);
        self::assertStringContainsString('trophy-card__edit', $card);
        self::assertStringContainsString('action=edit', $card);
        self::assertStringContainsString('>' . esc_html('Fiche') . '<', $card);
    }

    public function testCreateHandlerIsReservedToTheAuthenticatedAdministrator(): void
    {
        $hooks = [];
        TrophyCreateRequestHandler::register(static function (string $hook, $callback) use (&$hooks): void {
            $hooks[$hook] = $callback;
        });

        self::assertSame(
            [TrophyCreateRequestHandler::class, 'handle'],
            $hooks['admin_post_cat_trophy_create']
        );
        self::assertArrayNotHasKey('admin_post_nopriv_cat_trophy_create', $hooks);

        $template = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/template-parts/shell/trophees.php'
        );
        self::assertStringContainsString('cta_shell_user_is_administrator()', $template);
        self::assertStringContainsString('cat_trophy_create_form()', $template);
    }
}
