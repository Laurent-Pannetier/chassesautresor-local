<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleParticipationPolicyService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleParticipationPolicyService.php';

final class RiddleParticipationPolicyServiceTest extends TestCase {
    /** @dataProvider decisionProvider */
    public function testDecidesParticipationState(array $context, array $expected): void {
        $result = (new RiddleParticipationPolicyService())->decide(...$context);
        $this->assertSame($expected[0], $result['etat']);
        $this->assertSame($expected[1], $result['rediriger']);
        $this->assertSame($expected[2], $result['afficher_formulaire']);
    }

    /** @return array<string, array{array<int,mixed>,array{string,bool,bool}}> */
    public function decisionProvider(): array {
        return [
            'administrator' => [
                ['en_cours', true, true, false, false, false, false, true],
                ['en_cours', false, false],
            ],
            'draft' => [['en_cours', false, true, false, false, true, true, true], ['en_cours', true, false]],
            'organizer' => [['en_cours', false, false, true, false, false, false, true], ['en_cours', false, false]],
            'organizer draft' => [
                ['en_cours', false, true, true, false, true, true, true],
                ['en_cours', true, false],
            ],
            'finished hunt' => [
                ['en_cours', false, false, false, true, false, false, true],
                ['terminee', false, true],
            ],
            'not engaged' => [['en_cours', false, false, false, false, false, true, true], ['en_cours', true, false]],
            'prerequisite' => [
                ['en_cours', false, false, false, false, true, true, false],
                ['bloquee_pre_requis', true, false],
            ],
            'active player' => [['en_cours', false, false, false, false, true, true, true], ['en_cours', false, true]],
        ];
    }
}
