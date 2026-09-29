<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

/**
 * Award points purchased through WooCommerce orders.
 */
class PurchasePointsService
{
    private const POINT_PACKS = [
        'pack-100-points' => 100,
        'pack-500-points' => 500,
        'pack-1000-points' => 1000,
    ];

    private PointsService $points;

    public function __construct(PointsService $points)
    {
        $this->points = $points;
    }

    /**
     * Award an order once and return the number of points added.
     *
     * @param object $order WooCommerce order compatible object.
     */
    public function awardOrder($order): int
    {
        if (!$order || $order->get_meta('_points_deja_attribues')) {
            return 0;
        }

        $userId = (int) $order->get_user_id();

        if ($userId <= 0) {
            return 0;
        }

        $orderId = (int) $order->get_id();
        $total = 0;

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();

            if (!$product) {
                continue;
            }

            $slug = $product->get_slug();

            if (!isset(self::POINT_PACKS[$slug])) {
                continue;
            }

            $points = self::POINT_PACKS[$slug] * (int) $item->get_quantity();
            $reason = sprintf(
                /* translators: 1: number of points, 2: WooCommerce order ID. */
                __('Achat de %1$d points (commande #%2$d)', 'chassesautresor-com'),
                $points,
                $orderId
            );

            $this->points->add($userId, $points, $reason, 'achat', $orderId);
            $total += $points;
            $order->add_order_note(
                sprintf(
                    /* translators: %d: number of points awarded. */
                    __('✅ %d points ajoutés.', 'chassesautresor-com'),
                    $points
                )
            );
        }

        if ($total > 0) {
            $order->update_meta_data('_points_deja_attribues', true);
            $order->save();
        }

        return $total;
    }
}
