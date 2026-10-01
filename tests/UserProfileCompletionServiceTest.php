<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Users\UserProfileCompletionService;
use PHPUnit\Framework\TestCase;

final class UserProfileCompletionServiceTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testEvaluatesDefaultAndFilteredRequiredFields(): void {
        $GLOBALS['profile_meta'] = [
            'first_name' => 'Ada',
            'last_name' => '',
            'company' => 'Treasure Labs',
        ];

        function __(string $message, string $domain = ''): string {
            return $message;
        }
        function get_userdata(int $userId): object {
            return (object) [
                'ID' => $userId,
                'display_name' => 'Ada L.',
                'user_email' => 'ada@example.test',
            ];
        }
        function get_user_meta(int $userId, string $key, bool $single = false) {
            return $GLOBALS['profile_meta'][$key] ?? '';
        }
        function apply_filters(string $filter, $value, ...$arguments) {
            $value['company'] = 'Société';
            return $value;
        }
        function wp_sprintf_l(string $pattern, array $items): string {
            return implode(', ', $items);
        }

        $service = new UserProfileCompletionService();
        $result = $service->evaluate(12);

        self::assertFalse($result['complete']);
        self::assertSame(['Nom'], $result['missing']);
        self::assertSame(
            'Veuillez compléter votre profil utilisateur : Nom.',
            $service->missingFieldsMessage($result['missing'])
        );
    }
}
