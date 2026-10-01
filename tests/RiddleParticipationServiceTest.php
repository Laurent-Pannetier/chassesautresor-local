<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleParticipationService;
use PHPUnit\Framework\TestCase;

final class RiddleParticipationServiceTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLoadsRiddleAndHuntHintsWithPortableQueries(): void {
        $GLOBALS['hint_queries'] = [];
        function recuperer_id_chasse_associee(int $riddleId): int {
            return $riddleId === 12 ? 34 : 0;
        }
        function get_posts(array $args): array {
            $GLOBALS['hint_queries'][] = $args;

            return count($GLOBALS['hint_queries']) === 1 ? [5, '6'] : [7];
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleParticipationService.php';

        $ids = (new RiddleParticipationService())->hintIds(12);

        self::assertSame(['riddle' => [5, 6], 'hunt' => [7]], $ids);
        self::assertSame('enigme', $GLOBALS['hint_queries'][0]['meta_query'][0]['value']);
        self::assertSame('indice_chasse_linked', $GLOBALS['hint_queries'][1]['meta_query'][1]['key']);
        self::assertSame(['accessible', 'programme'], $GLOBALS['hint_queries'][1]['meta_query'][2]['value']);
    }
}
