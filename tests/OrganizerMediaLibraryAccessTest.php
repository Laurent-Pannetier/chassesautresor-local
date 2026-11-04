<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\WordPressAccessPolicyHookHandler;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class OrganizerMediaLibraryAccessTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_REQUEST['post_id'], $GLOBALS['organizer_media_user'], $GLOBALS['organizer_media_post_types']);
    }

    public function testOrganizerKeepsTheirWholeLibraryWhileEditingAnEnigma(): void
    {
        $this->asUser(42, ['organisateur']);
        $_REQUEST['post_id'] = '9';
        $GLOBALS['organizer_media_post_types'] = [9 => 'enigme'];
        $incoming = [
            'post_type' => 'attachment',
            'meta_query' => [
                [
                    'key' => '_wp_attached_file',
                    'value' => '2026/11',
                    'compare' => 'LIKE',
                ],
            ],
        ];

        $query = $this->libraryQuery($incoming);

        $this->assertSame(42, $query['author']);
        $this->assertSame($incoming['meta_query'], $query['meta_query']);
        $this->assertStringNotContainsString('_enigmes/enigme-9/', $this->queryText($query));
    }

    public function testOrganizerStillSeesEnigmaFilesFromAnotherPost(): void
    {
        $this->asUser(42, ['organisateur']);
        $_REQUEST['post_id'] = '3';
        $GLOBALS['organizer_media_post_types'] = [3 => 'chasse'];

        $query = $this->libraryQuery(['post_type' => 'attachment']);

        $this->assertSame(42, $query['author']);
        $this->assertArrayNotHasKey('meta_query', $query);
        $this->assertStringNotContainsString('NOT LIKE', $this->queryText($query));
    }

    public function testAnotherOrganizerDoesNotReceiveTheFirstAuthorsFiles(): void
    {
        $this->asUser(7, ['organisateur']);
        $_REQUEST['post_id'] = '9';
        $GLOBALS['organizer_media_post_types'] = [9 => 'enigme'];

        $query = $this->libraryQuery(['post_type' => 'attachment']);

        $this->assertSame(7, $query['author']);
        $this->assertNotSame(42, $query['author']);
    }

    public function testAdministratorKeepsTheUnfilteredLibrary(): void
    {
        $this->asUser(1, ['administrator']);
        $_REQUEST['post_id'] = '9';
        $GLOBALS['organizer_media_post_types'] = [9 => 'enigme'];

        $query = $this->libraryQuery(['post_type' => 'attachment']);

        $this->assertArrayNotHasKey('author', $query);
        $this->assertArrayNotHasKey('meta_query', $query);
    }

    public function testLoggedOutRequestDoesNotGainAnAuthorOrFolderScope(): void
    {
        $GLOBALS['organizer_media_user'] = new OrganizerMediaLibraryUser(0, [], false);
        $_REQUEST['post_id'] = '9';
        $GLOBALS['organizer_media_post_types'] = [9 => 'enigme'];
        $incoming = ['post_type' => 'attachment', 'posts_per_page' => 40];

        $this->assertSame($incoming, $this->libraryQuery($incoming));
    }

    private function asUser(int $id, array $roles): void
    {
        $GLOBALS['organizer_media_user'] = new OrganizerMediaLibraryUser($id, $roles);
    }

    private function libraryQuery(array $query): array
    {
        return WordPressAccessPolicyHookHandler::filterRiddleMedia(
            WordPressAccessPolicyHookHandler::restrictMediaToAuthor($query)
        );
    }

    private function queryText(array $query): string
    {
        $encoded = json_encode($query);

        return is_string($encoded) ? $encoded : '';
    }
}

class OrganizerMediaLibraryUser
{
    public int $ID;

    /** @var list<string> */
    public array $roles;

    private bool $present;

    public function __construct(int $id, array $roles, bool $present = true)
    {
        $this->ID = $id;
        $this->roles = $roles;
        $this->present = $present;
    }

    public function exists(): bool
    {
        return $this->present;
    }
}

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user(): OrganizerMediaLibraryUser
    {
        $user = $GLOBALS['organizer_media_user'] ?? null;

        return $user instanceof OrganizerMediaLibraryUser
            ? $user
            : new OrganizerMediaLibraryUser(0, [], false);
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($postId): string
    {
        $types = $GLOBALS['organizer_media_post_types'] ?? [];

        return (string) ($types[(int) $postId] ?? '');
    }
}
