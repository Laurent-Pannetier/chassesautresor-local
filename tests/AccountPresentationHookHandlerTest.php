<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Users\AccountPresentationHookHandler;
use PHPUnit\Framework\TestCase;

final class AccountPresentationHookHandlerTest extends TestCase {
    public function testRegistersAccountTitleFilters(): void {
        $filters = [];
        AccountPresentationHookHandler::register(
            static function (...$arguments) use (&$filters): void {
                $filters[] = $arguments;
            }
        );

        self::assertSame([
            ['pre_get_document_title', [AccountPresentationHookHandler::class, 'filterDocumentTitle']],
            ['woocommerce_endpoint_orders_title', [AccountPresentationHookHandler::class, 'ordersTitle']],
            ['woocommerce_endpoint_edit-account_title', [AccountPresentationHookHandler::class, 'profileTitle']],
        ], $filters);
    }

    public function testUsesTheCoreTextDomainForAccountLabels(): void {
        self::assertSame('Vos commandes', AccountPresentationHookHandler::ordersTitle('Orders'));
        self::assertSame('Profil', AccountPresentationHookHandler::profileTitle('Account'));
    }
}
