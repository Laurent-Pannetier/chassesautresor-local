<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Admin\OrganizerAccountTransferService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Admin/OrganizerAccountTransferService.php';

final class OrganizerAccountTransferServiceTest extends TestCase
{
    public function testPlanMovesTheAccountAuthorsMediaAndRole(): void
    {
        $plan = (new OrganizerAccountTransferService())->plan($this->snapshot());

        self::assertFalse($plan['blocked']);
        self::assertSame([], $plan['errors']);
        self::assertSame([8], $plan['associated_user_ids']);
        self::assertSame([10], $plan['force_author_ids']);
        self::assertEqualsCanonicalizing([20, 21, 100, 101], $plan['reauthor_ids']);
        self::assertSame([20 => 8], $plan['hunt_user_ids']);
        self::assertSame(['organisateur'], $plan['roles_granted']);
        self::assertSame(['organisateur'], $plan['roles_revoked']);
        self::assertContains('contact_email', $plan['notices']);
        self::assertNotContains(102, $plan['reauthor_ids']);
        self::assertSame('orphan', $plan['attachments'][1]['reason']);
    }

    public function testAuditConfirmsTheLinkWithoutOpeningTheTargetSession(): void
    {
        $service = new OrganizerAccountTransferService();
        $plan = $service->plan($this->snapshot());
        $checks = $service->audit([
            'associated_user_ids' => [8],
            'organizer_author' => 8,
            'target_roles' => ['subscriber', 'organisateur'],
            'source_roles' => ['subscriber'],
        ], $plan);

        self::assertSame(
            [
                ['code' => 'target_associated', 'ok' => true],
                ['code' => 'author_is_target', 'ok' => true],
                ['code' => 'source_detached', 'ok' => true],
                ['code' => 'role_granted', 'ok' => true],
                ['code' => 'role_revoked', 'ok' => true],
            ],
            $checks
        );
    }

    public function testAuditFlagsARoleThatDidNotMove(): void
    {
        $service = new OrganizerAccountTransferService();
        $plan = $service->plan($this->snapshot());
        $checks = $service->audit([
            'associated_user_ids' => [3],
            'organizer_author' => 3,
            'target_roles' => ['subscriber'],
            'source_roles' => ['subscriber', 'organisateur'],
        ], $plan);
        $byCode = [];
        foreach ($checks as $check) {
            $byCode[$check['code']] = $check['ok'];
        }

        self::assertFalse($byCode['target_associated']);
        self::assertFalse($byCode['author_is_target']);
        self::assertFalse($byCode['source_detached']);
        self::assertFalse($byCode['role_granted']);
        self::assertFalse($byCode['role_revoked']);
    }

    public function testPlanKeepsOtherAssociatedUsersAndAForeignAuthor(): void
    {
        $plan = (new OrganizerAccountTransferService())->plan($this->snapshot([
            'associated_user_ids' => [3, 4],
            'posts' => [
                $this->hunt(20, 3, 3),
                [
                    'id' => 21,
                    'type' => 'enigme',
                    'author' => 1,
                    'title' => 'Énigme admin',
                    'status' => 'publish',
                    'organizer_user_id' => 0,
                ],
            ],
        ]));

        self::assertFalse($plan['blocked']);
        self::assertSame([8, 4], $plan['associated_user_ids']);
        self::assertEqualsCanonicalizing([20, 100, 101], $plan['reauthor_ids']);
        self::assertSame(1, $plan['foreign_author_count']);
        self::assertContains('foreign_authors', $plan['notices']);
        self::assertGreaterThan(0, $plan['shared_attachment_count']);
    }

    public function testPlanLeavesADifferentHuntUserUntouchedAndFillsAnEmptyOne(): void
    {
        $plan = (new OrganizerAccountTransferService())->plan($this->snapshot([
            'posts' => [
                $this->hunt(20, 3, 99),
                $this->hunt(22, 3, 0),
            ],
        ]));

        self::assertSame([22 => 8], $plan['hunt_user_ids']);
    }

    public function testPlanCopiesTheCreationRoleAndSkipsOrphansWhenAsked(): void
    {
        $plan = (new OrganizerAccountTransferService())->plan($this->snapshot([
            'source_roles' => ['subscriber', 'organisateur_creation'],
            'include_orphan_attachments' => false,
        ]));

        self::assertSame(['organisateur_creation'], $plan['roles_granted']);
        self::assertSame(['organisateur_creation'], $plan['roles_revoked']);
        self::assertNotContains(101, $plan['reauthor_ids']);
        self::assertContains('orphans_skipped', $plan['notices']);
        self::assertSame(1, $plan['orphan_skipped_count']);
    }

    public function testPlanKeepsTheRoleWhenTheSourceStillOwnsAnotherOrganizer(): void
    {
        $plan = (new OrganizerAccountTransferService())->plan($this->snapshot([
            'source_other_organizer_ids' => [90],
        ]));

        self::assertSame(['organisateur'], $plan['roles_granted']);
        self::assertSame([], $plan['roles_revoked']);
        self::assertNotContains(101, $plan['reauthor_ids']);
        self::assertContains('source_keeps_roles', $plan['notices']);
    }

    public function testPlanBlocksATargetAlreadyLinkedToAnotherOrganizer(): void
    {
        $plan = (new OrganizerAccountTransferService())->plan($this->snapshot([
            'target_organizer_ids' => [55],
        ]));

        self::assertTrue($plan['blocked']);
        self::assertSame(['target_already_linked'], $plan['errors']);
        self::assertSame([], $plan['reauthor_ids']);
    }

    public function testPlanAllowsATargetAlreadyListedOnThisOrganizer(): void
    {
        $plan = (new OrganizerAccountTransferService())->plan($this->snapshot([
            'target_organizer_ids' => [10],
            'associated_user_ids' => [3, 8],
        ]));

        self::assertFalse($plan['blocked']);
        self::assertSame([8], $plan['associated_user_ids']);
    }

    public function testPlanBlocksAnUnlinkedSourceEvenIfTheyHaveLooseFiles(): void
    {
        $plan = (new OrganizerAccountTransferService())->plan($this->snapshot([
            'organizer_author' => 1,
            'associated_user_ids' => [4],
            'posts' => [$this->hunt(20, 1, 4)],
            'attachments' => [
                ['id' => 101, 'author' => 3, 'title' => 'libre.jpg', 'parent_id' => 0, 'referenced' => false],
            ],
        ]));

        self::assertTrue($plan['blocked']);
        self::assertSame(['source_not_linked'], $plan['errors']);
    }

    public function testPlanReportsAnAlreadyTransferredOrganizer(): void
    {
        $plan = (new OrganizerAccountTransferService())->plan($this->snapshot([
            'organizer_author' => 8,
            'associated_user_ids' => [8],
            'target_organizer_ids' => [10],
            'posts' => [$this->hunt(20, 8, 8)],
            'attachments' => [],
        ]));

        self::assertTrue($plan['blocked']);
        self::assertSame(['already_transferred'], $plan['errors']);
    }

    public function testPlanReportsMissingUsersAndAMissingOrganizerTogether(): void
    {
        $plan = (new OrganizerAccountTransferService())->plan($this->snapshot([
            'organizer_id' => 0,
            'organizer_type' => '',
            'organizer_status' => '',
            'source_exists' => false,
            'target_exists' => false,
        ]));

        self::assertSame(['invalid_organizer', 'missing_source', 'missing_target'], $plan['errors']);
    }

    public function testPlanRejectsTheSameAccountAndATrashedOrganizer(): void
    {
        $same = (new OrganizerAccountTransferService())->plan($this->snapshot([
            'target_user_id' => 3,
            'target_exists' => true,
        ]));
        $trashed = (new OrganizerAccountTransferService())->plan($this->snapshot([
            'organizer_status' => 'trash',
        ]));

        self::assertSame(['same_user'], $same['errors']);
        self::assertSame(['invalid_organizer'], $trashed['errors']);
    }

    public function testContainsIdDoesNotConfuseAShortUserIdWithALongerOne(): void
    {
        $service = new OrganizerAccountTransferService();

        self::assertTrue($service->containsId('a:1:{i:0;s:3:"123";}', 123));
        self::assertFalse($service->containsId('a:1:{i:0;s:3:"123";}', 12));
        self::assertTrue($service->containsId('a:1:{i:0;s:2:"12";}', 12));
        self::assertFalse($service->containsId('a:1:{i:0;s:2:"12";}', 123));
        self::assertTrue($service->containsId(['12', '8'], 12));
        self::assertTrue($service->containsId(12, 12));
    }

    public function testCandidateAttachmentIdsIgnoreBusinessNumbersAndRelationshipFields(): void
    {
        $ids = (new OrganizerAccountTransferService())->candidateAttachmentIds([
            ['key' => 'logo_organisateur', 'value' => '3927'],
            ['key' => 'indice_cout_points', 'value' => '5'],
            ['key' => 'etape_image', 'value' => 'a:2:{i:0;s:4:"3927";i:1;i:3928;}'],
            ['key' => '_logo_organisateur', 'value' => '3927'],
            ['key' => 'chasse_cache_organisateur', 'value' => 'a:1:{i:0;s:2:"10";}'],
            ['key' => 'enigme_visuel_image', 'value' => ['30', 31]],
        ]);

        self::assertEqualsCanonicalizing([3927, 3928, 30, 31], $ids);
    }

    public function testPluginRegistersTheTransferPage(): void
    {
        $bootstrap = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/chassesautresor-core.php'
        );

        self::assertStringContainsString('OrganizerAccountTransferService.php', $bootstrap);
        self::assertStringContainsString('OrganizerAccountTransferPage.php', $bootstrap);
        self::assertStringContainsString('OrganizerAccountTransferPage::register', $bootstrap);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function snapshot(array $overrides = []): array
    {
        return array_merge([
            'organizer_id' => 10,
            'organizer_type' => 'organisateur',
            'organizer_status' => 'publish',
            'organizer_author' => 3,
            'organizer_title' => 'Club test',
            'associated_user_ids' => [3],
            'contact_email' => 'faux@example.com',
            'source_user_id' => 3,
            'source_exists' => true,
            'source_roles' => ['subscriber', 'organisateur'],
            'source_other_organizer_ids' => [],
            'target_user_id' => 8,
            'target_exists' => true,
            'target_email' => 'vrai@example.com',
            'target_roles' => ['subscriber'],
            'target_organizer_ids' => [],
            'include_orphan_attachments' => true,
            'posts' => [
                $this->hunt(20, 3, 3),
                [
                    'id' => 21,
                    'type' => 'enigme',
                    'author' => 3,
                    'title' => 'Énigme 1',
                    'status' => 'pending',
                    'organizer_user_id' => 0,
                ],
            ],
            'attachments' => [
                ['id' => 100, 'author' => 3, 'title' => 'logo.jpg', 'parent_id' => 10, 'referenced' => true],
                ['id' => 101, 'author' => 3, 'title' => 'brouillon.jpg', 'parent_id' => 0, 'referenced' => false],
                ['id' => 102, 'author' => 1, 'title' => 'defaut.jpg', 'parent_id' => 10, 'referenced' => true],
            ],
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function hunt(int $id, int $author, int $userId): array
    {
        return [
            'id' => $id,
            'type' => 'chasse',
            'author' => $author,
            'title' => 'La chasse',
            'status' => 'publish',
            'organizer_user_id' => $userId,
        ];
    }
}
