<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Messages\AccountOrganizersRenderer;
use PHPUnit\Framework\TestCase;

final class AccountOrganizersRendererTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersFiltersAndDelegatesTheTable(): void {
        function __($value): string {
            return (string) $value;
        }
        function esc_html__($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_html($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_attr($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function _n($single, $plural, int $count): string {
            return $count === 1 ? $single : $plural;
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Messages/AccountOrganizersRenderer.php';

        $provider = static fn (): array => [
            ['statut' => 'en_attente'],
            ['statut' => 'valide'],
        ];
        $table = static fn (array $rows, int $page): string => sprintf(
            '<table data-count="%d" data-page="%d"></table>',
            count($rows),
            $page
        );
        $html = (new AccountOrganizersRenderer($provider, $table))->render(2);

        self::assertStringContainsString('2 organisateurs', $html);
        self::assertStringContainsString('value="en_attente"', $html);
        self::assertStringContainsString('data-count="2" data-page="2"', $html);
    }
}
