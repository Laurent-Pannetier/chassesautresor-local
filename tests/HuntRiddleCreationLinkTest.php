<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HuntRiddleCreationLinkTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLinkIsPresentWhenAddingIsAllowed(): void
    {
        $this->stubWordPress(true);

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Content/hunt-lifecycle-functions.php';

        $link = cat_hunt_riddle_creation_link(7, 3);

        self::assertTrue($link['allowed']);
        self::assertStringContainsString('/creer-enigme/', $link['url']);
        self::assertStringContainsString('chasse_id=7', $link['url']);
        self::assertStringContainsString('nonce=creer_enigme', $link['url']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLinkStaysEmptyWhenAddingIsForbidden(): void
    {
        $this->stubWordPress(false);

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Content/hunt-lifecycle-functions.php';

        $link = cat_hunt_riddle_creation_link(7, 3);

        self::assertFalse($link['allowed']);
        self::assertSame('', $link['url']);
    }

    private function stubWordPress(bool $allowed): void
    {
        $flag = $allowed ? 'true' : 'false';
        eval(
            'function utilisateur_peut_ajouter_enigme($huntId, $userId = null) {'
            . ' return ' . $flag . ' && (int) $huntId === 7 && (int) $userId === 3;'
            . '}'
        );
        eval('function get_current_user_id() { return 3; }');
        eval('function wp_create_nonce($action) { return (string) $action; }');
        eval('function home_url($path = "/") { return "https://example.test" . $path; }');
        eval(
            'function add_query_arg($args, $url) {'
            . ' return $url . "?" . http_build_query($args);'
            . '}'
        );
    }
}
