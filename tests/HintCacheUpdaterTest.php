<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintCacheService;
use ChassesAuTresor\Core\Content\HintCacheUpdater;
use ChassesAuTresor\Core\Content\HintStatusService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintStatusService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintCacheService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintCacheUpdater.php';

final class HintCacheUpdaterTest extends TestCase {
    private HintCacheUpdater $updater;
    private array $fields;
    private array $updatedFields;
    private array $updatedPosts;

    protected function setUp(): void {
        $this->updater = new HintCacheUpdater(new HintCacheService(new HintStatusService()));
        $this->fields = [];
        $this->updatedFields = [];
        $this->updatedPosts = [];
    }

    public function testPublishesCompleteImmediateHintAndUpdatesCache(): void {
        $this->fields = [
            'indice_contenu' => 'A clue',
            'indice_image' => null,
            'indice_disponibilite' => 'immediate',
        ];

        $result = $this->update('pending');

        $this->assertSame(1, $result['complete']);
        $this->assertSame('accessible', $result['state']);
        $this->assertSame([
            'indice_cache_complet' => 1,
            'indice_cache_etat_systeme' => 'accessible',
        ], $this->updatedFields);
        $this->assertSame('publish', $this->updatedPosts[0]['post_status']);
        $this->assertTrue($this->updatedPosts[0]['edit_date']);
    }

    public function testFutureHintIsProgrammedWithoutChangingPostStatus(): void {
        $this->fields = [
            'indice_image' => 18,
            'indice_disponibilite' => 'differe',
            'indice_date_disponibilite' => '2026-10-02 18:00:00',
        ];

        $result = $this->update('draft');

        $this->assertSame('programme', $result['state']);
        $this->assertNull($result['publication_status']);
        $this->assertSame([], $this->updatedPosts);
    }

    public function testInvalidDeferredDateDisablesPublishedHint(): void {
        $this->fields = [
            'indice_contenu' => 'A clue',
            'indice_disponibilite' => 'differe',
            'indice_date_disponibilite' => 'invalid',
        ];

        $result = $this->update('publish');

        $this->assertSame(0, $result['complete']);
        $this->assertSame('desactive', $result['state']);
        $this->assertSame('pending', $this->updatedPosts[0]['post_status']);
    }

    public function testMissingPostStillUpdatesCacheWithoutPublicationWrite(): void {
        $this->fields = [
            'indice_contenu' => 'A clue',
            'indice_disponibilite' => 'immediate',
        ];

        $this->update('pending', false);

        $this->assertSame(1, $this->updatedFields['indice_cache_complet']);
        $this->assertSame([], $this->updatedPosts);
    }

    private function update(string $postStatus, $post = null): array {
        if ($post === null) {
            $post = (object) [
                'post_date' => '2026-09-30 10:00:00',
                'post_date_gmt' => '2026-09-30 10:00:00',
            ];
        }

        return $this->updater->update(
            42,
            strtotime('2026-09-30 12:00:00'),
            fn (string $field) => $this->fields[$field] ?? null,
            static function (string $date): ?DateTimeImmutable {
                $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date);

                return $parsed ?: null;
            },
            static fn (): string => $postStatus,
            static fn () => $post,
            function (string $field, $value): void {
                $this->updatedFields[$field] = $value;
            },
            function (array $postData): void {
                $this->updatedPosts[] = $postData;
            }
        );
    }
}
