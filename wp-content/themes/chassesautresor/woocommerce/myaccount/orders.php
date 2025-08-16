<?php
/**
 * Orders
 *
 * Shows orders on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/orders.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.5.0
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$roles        = (array) $current_user->roles;
$is_organizer = in_array(ROLE_ORGANISATEUR, $roles, true) || in_array(ROLE_ORGANISATEUR_CREATION, $roles, true);

do_action('woocommerce_before_account_orders', $has_orders);

if ($is_organizer) {
    if (function_exists('charger_script_conversion')) {
        charger_script_conversion(true);
    }

    $user_id        = (int) $current_user->ID;
    $organisateur_id = function_exists('get_organisateur_from_user') ? get_organisateur_from_user($user_id) : null;
    $user_points     = function_exists('get_user_points') ? get_user_points($user_id) : 0;
    $access_message  = function_exists('verifier_acces_conversion') ? verifier_acces_conversion($user_id) : false;
    $conversion_disabled = $access_message !== true;
    $peut_editer     = $organisateur_id && function_exists('utilisateur_peut_editer_champs')
        ? utilisateur_peut_editer_champs($organisateur_id)
        : false;
    $iban = $organisateur_id ? get_field('iban', $organisateur_id) : '';
    $bic  = $organisateur_id ? get_field('bic', $organisateur_id) : '';
    $coordonnees_vides = empty($iban) && empty($bic);
    ?>
    <div class="dashboard-grid stats-cards myaccount-points-cards">
        <div class="dashboard-card" data-stat="points">
            <i class="fa-solid fa-coins" aria-hidden="true"></i>
            <h3><?php esc_html_e('Points', 'chassesautresor-com'); ?></h3>
            <p class="stat-value"><?php echo esc_html($user_points); ?></p>
        </div>
        <div class="dashboard-card<?php echo $conversion_disabled ? ' disabled' : ''; ?>" data-stat="conversion">
            <i class="fa-solid fa-right-left" aria-hidden="true"></i>
            <h3>Conversion</h3>
            <button type="button" id="open-conversion-modal" class="stat-value">
                <?php esc_html_e('Convertir', 'chassesautresor-com'); ?>
            </button>
        </div>
        <div class="dashboard-card" data-stat="bank-details">
            <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
            <h3>
                Coordonnées bancaires
                <button
                    type="button"
                    class="mode-fin-aide stat-help"
                    data-message="<?php echo esc_attr__('Ces informations sont nécessaires uniquement pour vous verser les gains issus de la conversion de vos points en euros. Nous ne prélevons jamais d\'argent.', 'chassesautresor-com'); ?>"
                    aria-label="<?php esc_attr_e('Informations sur les coordonnées bancaires', 'chassesautresor-com'); ?>"
                >
                    <i class="fa-regular fa-circle-question" aria-hidden="true"></i>
                </button>
            </h3>
            <?php if ($peut_editer) : ?>
                <?php
                $bank_label = $coordonnees_vides ? __('Ajouter', 'chassesautresor-com') : __('Éditer', 'chassesautresor-com');
                $bank_aria  = $coordonnees_vides ? __('Ajouter des coordonnées bancaires', 'chassesautresor-com') : __('Modifier les coordonnées bancaires', 'chassesautresor-com');
                ?>
                <a
                    id="ouvrir-coordonnees"
                    class="stat-value champ-modifier"
                    href="#"
                    aria-label="<?php echo esc_attr($bank_aria); ?>"
                    data-champ="coordonnees_bancaires"
                    data-cpt="organisateur"
                    data-post-id="<?php echo esc_attr($organisateur_id); ?>"
                    data-label-add="<?php esc_attr_e('Ajouter', 'chassesautresor-com'); ?>"
                    data-label-edit="<?php esc_attr_e('Éditer', 'chassesautresor-com'); ?>"
                    data-aria-add="<?php esc_attr_e('Ajouter des coordonnées bancaires', 'chassesautresor-com'); ?>"
                    data-aria-edit="<?php esc_attr_e('Modifier les coordonnées bancaires', 'chassesautresor-com'); ?>"
                ><?php echo esc_html($bank_label); ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    get_template_part('template-parts/modals/modal-conversion');
} else {
    $points = function_exists('get_user_points') ? get_user_points() : 0;

    if ($points > 0) {
        echo '<p class="myaccount-points">' . sprintf(esc_html__('Vous avez %d points', 'chassesautresor'), $points) . '</p>';
    } else {
        $shop_url = esc_url(home_url('/boutique'));
        echo '<p class="myaccount-points"><a href="' . $shop_url . '">' . esc_html__('Ajouter des points', 'chassesautresor') . '</a></p>';
    }
}

if ($has_orders) : ?>

<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
<thead>
<tr>
<?php foreach (wc_get_account_orders_columns() as $column_id => $column_name) : ?>
<th scope="col" class="woocommerce-orders-table__header woocommerce-orders-table__header-<?php echo esc_attr($column_id); ?>"><span class="nobr"><?php echo esc_html($column_name); ?></span></th>
<?php endforeach; ?>
</tr>
</thead>

<tbody>
<?php
foreach ($customer_orders->orders as $customer_order) {
$order      = wc_get_order($customer_order); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$item_count = $order->get_item_count() - $order->get_item_count_refunded();
?>
<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr($order->get_status()); ?> order">
<?php foreach (wc_get_account_orders_columns() as $column_id => $column_name) :
$is_order_number = 'order-number' === $column_id;
?>
<?php if ($is_order_number) : ?>
<th class="woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr($column_id); ?>" data-title="<?php echo esc_attr($column_name); ?>" scope="row">
<?php else : ?>
<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr($column_id); ?>" data-title="<?php echo esc_attr($column_name); ?>">
<?php endif; ?>
<?php if (has_action('woocommerce_my_account_my_orders_column_' . $column_id)) : ?>
<?php do_action('woocommerce_my_account_my_orders_column_' . $column_id, $order); ?>
<?php elseif ('order-number' === $column_id) : ?>
<a href="<?php echo esc_url($order->get_view_order_url()); ?>">
<?php echo _x('#', 'hash before order number', 'woocommerce') . $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</a>
<?php elseif ('order-date' === $column_id) : ?>
<time datetime="<?php echo esc_attr($order->get_date_created()->date('c')); ?>"><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></time>
<?php elseif ('order-status' === $column_id) : ?>
<?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
<?php elseif ('order-total' === $column_id) : ?>
<?php echo wp_kses_post($order->get_formatted_order_total()); ?>
<?php elseif ('order-actions' === $column_id) : ?>
<?php $actions = wc_get_account_orders_actions($order); ?>
<?php if (!empty($actions)) : ?>
<?php foreach ($actions as $key => $action) : ?>
<a href="<?php echo esc_url($action['url']); ?>" class="woocommerce-button button <?php echo sanitize_html_class($key); ?>"><?php echo esc_html($action['name']); ?></a>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>
<?php if ($is_order_number) : ?>
</th>
<?php else : ?>
</td>
<?php endif; ?>
<?php endforeach; ?>
</tr>
<?php
}
?>
</tbody>
</table>

<?php do_action('woocommerce_after_account_orders', $has_orders); ?>

<?php else : ?>
<div class="woocommerce-message woocommerce-message--info woocommerce-MyAccount-orders--no-orders">
<?php echo esc_html__('No order has been made yet.', 'woocommerce') . ' '; ?>
<a class="woocommerce-Button button" href="<?php echo esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))); ?>">
<?php esc_html_e('Browse products', 'woocommerce'); ?>
</a>
</div>
<?php endif; ?>
