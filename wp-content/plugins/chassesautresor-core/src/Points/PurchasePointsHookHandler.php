<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/**
 * Award purchased points when WooCommerce completes its thank-you flow.
 */
final class PurchasePointsHookHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('woocommerce_thankyou', [self::class, 'handle']);
    }

    public static function handle(int $orderId): void
    {
        global $wpdb;

        CoreServiceFactory::purchasePoints($wpdb)->awardOrder(wc_get_order($orderId));

        if (!is_admin() && function_exists('WC') && WC()->cart) {
            WC()->cart->empty_cart();
        }
    }
}
