<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAnswerSubmissionPolicy;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAnswerSubmissionPolicy.php';

final class RiddleAnswerSubmissionPolicyTest extends TestCase {
    /** @dataProvider rejectionProvider */
    public function testRejectsInvalidSubmission(array $arguments, string $expected): void
    {
        $this->assertSame($expected, (new RiddleAnswerSubmissionPolicy())->validate(...$arguments));
    }

    public function rejectionProvider(): array
    {
        $valid = [true, true, 42, 'enigme', 'answer', 'accessible', 'en_cours', 3, 0, 10, 20, true];

        return [
            'anonymous' => [$this->replace($valid, 0, false), 'non_connecte'],
            'nonce' => [$this->replace($valid, 1, false), 'invalide'],
            'CPT' => [$this->replace($valid, 3, 'post'), 'invalide'],
            'empty answer' => [$this->replace($valid, 4, ''), 'invalide'],
            'system state' => [$this->replace($valid, 5, 'bloquee'), 'interdit'],
            'solved' => [$this->replace($valid, 6, 'resolue'), 'deja_resolue'],
            'daily limit' => [$this->replace($valid, 8, 3), 'tentatives_epuisees'],
            'points' => [$this->replace($valid, 10, 9), 'points_insuffisants'],
            'incomplete steps' => [$this->replace($valid, 12, false), 'etapes_incompletes'],
        ];
    }

    public function testAllowsValidAutomaticAndManualSubmissions(): void
    {
        $policy = new RiddleAnswerSubmissionPolicy();
        $base = [true, true, 42, 'enigme', 'answer', 'accessible', 'en_cours', 3, 0, 10, 20];

        $this->assertNull($policy->validate(...array_merge($base, [true])));
        $this->assertNull($policy->validate(...array_merge($base, [false])));
    }

    private function replace(array $arguments, int $index, $value): array
    {
        $arguments[$index] = $value;

        return $arguments;
    }
}
