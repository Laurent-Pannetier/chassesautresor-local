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
$roles        = (array) $current_user->roles;
$is_organizer = in_array(ROLE_ORGANISATEUR, $roles, true) || in_array(ROLE_ORGANISATEUR_CREATION, $roles, true);

if (current_user_can('administrator')) {
    global $wpdb;
    $repo               = new PointsRepository($wpdb);
    $used_points        = $repo->getTotalPointsUsed();
    $circulation_points = $repo->getTotalPointsInCirculation();
    ?>
    <div class="dashboard-grid stats-cards myaccount-points-cards">
        <div class="dashboard-card" data-stat="points-used">
            <i class="fa-solid fa-hand-holding-dollar" aria-hidden="true"></i>
            <h3><?php esc_html_e('Points utilisés', 'chassesautresor-com'); ?></h3>
            <p class="stat-value"><?php echo esc_html($used_points); ?></p>
        </div>
        <div class="dashboard-card" data-stat="points-bought">
            <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
            <h3><?php esc_html_e('Points achetés', 'chassesautresor-com'); ?></h3>
            <p class="stat-value"><?php esc_html_e('À implémenter', 'chassesautresor-com'); ?></p>
        </div>
        <div class="dashboard-card" data-stat="points-circulation">
            <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
            <h3><?php esc_html_e('Points en circulation', 'chassesautresor-com'); ?></h3>
            <p class="stat-value"><?php echo esc_html($circulation_points); ?></p>
        </div>
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-coins"></i>
                <h3><?php esc_html_e('Gestion Points', 'chassesautresor'); ?></h3>
            </div>
            <div class="stats-content">
                <form method="POST" class="form-gestion-points">
                    <?php wp_nonce_field('gestion_points_action', 'gestion_points_nonce'); ?>
                    <div class="gestion-points-ligne">
                        <label for="utilisateur-points"></label>
                        <input type="text" id="utilisateur-points" placeholder="Rechercher un utilisateur..." required>
                        <input type="hidden" id="utilisateur-id" name="utilisateur">
                        <label for="type-modification"></label>
                        <select id="type-modification" name="type_modification" required>
                            <option value="ajouter">➕</option>
                            <option value="retirer">➖</option>
                        </select>
                    </div>
                    <div class="gestion-points-ligne">
                        <label for="nombre-points"></label>
                        <input type="number" id="nombre-points" name="nombre_points" placeholder="nb de points" min="1" required>
                        <button type="submit" name="modifier_points" class="btn-icon bouton-tertiaire">✅</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php echo render_conversion_history(); ?>
    <?php
} elseif ($is_organizer) {
    $user_id         = (int) $current_user->ID;
    $user_points     = function_exists('get_user_points') ? get_user_points($user_id) : 0;
    ?>
    <div class="dashboard-grid stats-cards myaccount-points-cards">
        <div class="dashboard-card" data-stat="points">
            <i class="fa-solid fa-coins" aria-hidden="true"></i>
            <h3><?php esc_html_e('Points', 'chassesautresor-com'); ?></h3>
            <p class="stat-value"><?php echo esc_html($user_points); ?></p>
        </div>
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
