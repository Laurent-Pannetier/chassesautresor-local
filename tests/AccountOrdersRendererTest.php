<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Users\AccountOrdersRenderer;
use PHPUnit\Framework\TestCase;

final class AccountOrdersRendererTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersCompletedOrdersAndAnInternationalizedFallbackProduct(): void {
        $GLOBALS['orders_query'] = [];
        function wc_get_orders(array $arguments): array {
            $GLOBALS['orders_query'] = $arguments;
            return [new AccountOrdersTestOrder()];
        }
        function wc_format_datetime($date, string $format): string {
            return '02/10/2026';
        }
        function __(string $message, string $domain = ''): string {
            return $message;
        }
        function esc_html(string $value): string {
            return $value;
        }

        $html = (new AccountOrdersRenderer())->render(17, 3);

        self::assertSame(17, $GLOBALS['orders_query']['customer']);
        self::assertSame(3, $GLOBALS['orders_query']['limit']);
        self::assertSame(['wc-completed'], $GLOBALS['orders_query']['status']);
        self::assertStringContainsString('#81', $html);
        self::assertStringContainsString('Produit inconnu', $html);
        self::assertStringContainsString('02/10/2026', $html);
    }
}

final class AccountOrdersTestOrder {
    public function getItems(): array {
        return [];
    }

    public function get_items(): array {
        return $this->getItems();
    }

    public function get_id(): int {
        return 81;
    }

    public function get_date_created(): string {
        return '2026-10-02';
    }
}
