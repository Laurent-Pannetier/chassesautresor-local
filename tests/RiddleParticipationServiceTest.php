<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleParticipationService;
use ChassesAuTresor\Core\Progress\HintUnlockService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HintUnlockService.php';

final class ParticipationHintUnlockServiceStub extends HintUnlockService {
    public array $calls = [];

    public function __construct() {
    }

    public function unlockedHintIds(int $userId, array $hintIds): array {
        $this->calls[] = [$userId, $hintIds];

        return [5];
    }
}

final class RiddleParticipationServiceTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLoadsRiddleAndHuntHintsWithPortableQueries(): void {
        $GLOBALS['hint_queries'] = [];
        $GLOBALS['hint_field_calls'] = [];
        function recuperer_id_chasse_associee(int $riddleId): int {
            return $riddleId === 12 ? 34 : 0;
        }
        function get_posts(array $args): array {
            $GLOBALS['hint_queries'][] = $args;

            return count($GLOBALS['hint_queries']) === 1
                ? [(object) ['ID' => 5], (object) ['ID' => 6]]
                : [(object) ['ID' => 7]];
        }
        function get_field(string $field, int $hintId) {
            $GLOBALS['hint_field_calls'][] = [$field, $hintId];
            $values = [
                'indice_cout_points' => 5,
                'indice_cache_etat_systeme' => 'accessible',
                'indice_date_disponibilite' => '',
            ];

            return $values[$field] ?? null;
        }
        function get_indice_title(int $hintId): string {
            return 'Indice ' . $hintId;
        }
        function wp_timezone(): DateTimeZone {
            return new DateTimeZone('UTC');
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleParticipationService.php';

        $ids = (new RiddleParticipationService())->hintIds(12);

        self::assertSame(['riddle' => [5, 6], 'hunt' => [7]], $ids);
        self::assertSame('enigme', $GLOBALS['hint_queries'][0]['meta_query'][0]['value']);
        self::assertSame('indice_chasse_linked', $GLOBALS['hint_queries'][1]['meta_query'][1]['key']);
        self::assertSame(['accessible', 'programme'], $GLOBALS['hint_queries'][1]['meta_query'][2]['value']);
        self::assertTrue($GLOBALS['hint_queries'][0]['update_post_meta_cache']);
        self::assertFalse($GLOBALS['hint_queries'][0]['update_post_term_cache']);
        self::assertArrayNotHasKey('fields', $GLOBALS['hint_queries'][0]);

        $GLOBALS['hint_queries'] = [];
        $unlockService = new ParticipationHintUnlockServiceStub();
        $hints = (new RiddleParticipationService($unlockService))->hints(12, 9);
        self::assertSame(5, $hints['riddle'][0]['cost']);
        self::assertTrue($hints['riddle'][0]['unlocked']);
        self::assertSame('Indice 7', $hints['hunt'][0]['title']);
        self::assertSame([[9, [5, 6, 7]]], $unlockService->calls);
        self::assertCount(9, $GLOBALS['hint_field_calls']);
    }
}
