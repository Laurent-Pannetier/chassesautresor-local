<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAnswerContextService;
use PHPUnit\Framework\TestCase;

final class RiddleAnswerContextServiceTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBuildsManualAnswerPointContext(): void {
        function enigme_get_statut_utilisateur(int $riddleId, int $userId): string {
            return 'en_cours';
        }
        function get_field(string $field, int $postId): int {
            return 30;
        }
        function get_user_points(int $userId): int {
            return 20;
        }
        function __(string $message, string $domain = ''): string {
            return $message;
        }
        function home_url(string $path): string {
            return 'https://example.test' . $path;
        }
        function get_option(string $name, $default) {
            return $default;
        }

        $service = new RiddleAnswerContextService();
        self::assertTrue($service->canAnswer(4, 8));
        self::assertSame(10, $service->points(4, 8)['points_manquants']);
        self::assertSame(-10, $service->points(4, 8)['solde_apres']);
    }
}
