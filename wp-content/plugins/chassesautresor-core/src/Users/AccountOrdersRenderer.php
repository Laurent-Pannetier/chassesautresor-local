<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Users;

/** Render a compact table of a user's completed WooCommerce orders. */
final class AccountOrdersRenderer {
    public function render(int $userId, int $limit = 4): string {
        if ($userId <= 0 || !function_exists('wc_get_orders')) {
            return '';
        }

        $orders = wc_get_orders([
            'limit' => max(1, $limit),
            'customer' => $userId,
            'status' => ['wc-completed'],
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        if (empty($orders)) {
            return '';
        }

        ob_start();
        ?>
        <table class="stats-table">
            <tbody>
                <?php foreach ($orders as $order) :
                    $items = $order->get_items();
                    $firstItem = reset($items);
                    $productName = $firstItem
                        ? $firstItem->get_name()
                        : __('Produit inconnu', 'chassesautresor-com');
                    ?>
                    <tr>
                        <td>#<?= esc_html((string) $order->get_id()); ?></td>
                        <td><?= esc_html($productName); ?></td>
                        <td><?= esc_html(wc_format_datetime($order->get_date_created(), 'd/m/Y')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return trim((string) ob_get_clean());
    }
}
