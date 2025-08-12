<?php
defined('ABSPATH') || exit;

$balance   = function_exists('get_user_points') ? get_user_points() : 0;
$shop_link = wc_get_page_permalink('shop');
?>

<h2><?php esc_html_e('Mes points', 'chassesautresor-com'); ?></h2>
<p>
    <?php esc_html_e('Solde actuel :', 'chassesautresor-com'); ?>
    <?php echo esc_html($balance); ?>
    <?php esc_html_e('points', 'chassesautresor-com'); ?>
    <?php if (0 === $balance && $shop_link) : ?>
        <a href="<?php echo esc_url($shop_link); ?>">
            <?php esc_html_e('Boutique', 'chassesautresor-com'); ?>
        </a>
    <?php endif; ?>
</p>

<h3><?php esc_html_e('Historique de vos achats', 'chassesautresor-com'); ?></h3>
<?php
$current_page = isset($_GET['order-page']) ? absint($_GET['order-page']) : 1;
woocommerce_account_orders($current_page);
?>
