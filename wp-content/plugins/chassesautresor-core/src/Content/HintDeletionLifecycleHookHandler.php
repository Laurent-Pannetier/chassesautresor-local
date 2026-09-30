<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Preserve hint ordering targets across permanent WordPress deletion hooks.
 */
class HintDeletionLifecycleHookHandler {
    /** @var array{reorder_targets:array<int, array{id:int,type:string}>}|null */
    private static ?array $context = null;

    public static function register(callable $addAction): void {
        $addAction('before_delete_post', [self::class, 'capture'], 10, 1);
        $addAction('deleted_post', [self::class, 'reorder'], 10, 1);
    }

    public static function capture(int $postId): void {
        self::$context = (new HintDeletionLifecycleService())->capture(
            (string) get_post_type($postId),
            (string) get_field('indice_cible_type', $postId),
            get_field('indice_chasse_linked', $postId),
            get_field('indice_enigme_linked', $postId),
            static fn (int $riddleId): int => (int) (new RelationshipService())->normalizeId(
                get_field('enigme_chasse_associee', $riddleId)
            )
        );
    }

    public static function reorder(int $postId): void {
        $targets = (new HintDeletionLifecycleService())->restoreTargets(self::$context);
        self::$context = null;

        foreach ($targets as $target) {
            (new HintOrderingApplicationService())->applyTarget($target['id'], $target['type']);
        }
    }
}
