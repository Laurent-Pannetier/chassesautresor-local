<?php
/**
 * Points section for "Mon Compte".
 *
 * Displays points related cards and tables depending on user role.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();

if (current_user_can('administrator')) {
    return;
}

$roles        = (array) $current_user->roles;
$is_organizer = in_array(ROLE_ORGANISATEUR, $roles, true) || in_array(ROLE_ORGANISATEUR_CREATION, $roles, true);

if ($is_organizer) {
    $user_id         = (int) $current_user->ID;
    $organisateur_id = function_exists('get_organisateur_from_user') ? get_organisateur_from_user($user_id) : null;
    $user_points     = function_exists('get_user_points') ? get_user_points($user_id) : 0;
    ?>
    <div class="dashboard-grid stats-cards myaccount-points-cards">
        <div class="dashboard-card" data-stat="points">
            <i class="fa-solid fa-coins" aria-hidden="true"></i>
            <h3><?php esc_html_e('Points', 'chassesautresor-com'); ?></h3>
            <p class="stat-value"><?php echo esc_html($user_points); ?></p>
        </div>
        <?php if ($organisateur_id) : ?>
        <div class="dashboard-card" data-stat="organizer-points">
            <i class="fa-solid fa-landmark" aria-hidden="true"></i>
            <h3><?php esc_html_e('Points de mon organisation', 'chassesautresor-com'); ?></h3>
            <a
                class="stat-value"
                href="<?php echo esc_url(add_query_arg(['edition' => 'open', 'onglet' => 'revenus'], get_permalink($organisateur_id))); ?>"
            ><?php esc_html_e('Gérer', 'chassesautresor-com'); ?></a>
        </div>
        <?php endif; ?>
    </div>
    <?php
} else {
    $points = function_exists('get_user_points') ? get_user_points((int) $current_user->ID) : 0;
    ?>
    <div class="dashboard-grid stats-cards myaccount-points-cards">
        <div class="dashboard-card" data-stat="points">
            <i class="fa-solid fa-coins" aria-hidden="true"></i>
            <h3><?php esc_html_e('Points', 'chassesautresor-com'); ?></h3>
            <p class="stat-value"><?php echo esc_html($points); ?></p>
        </div>
    </div>
    <?php
    if ($points === 0) {
        $shop_url = esc_url(home_url('/boutique'));
        echo '<p class="myaccount-points"><a href="' . $shop_url . '">' . esc_html__('Ajouter des points', 'chassesautresor') . '</a></p>';
    }
}

echo render_points_history_table((int) $current_user->ID);
