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
    $historique = recuperer_historique_paiements_admin();
    $points     = function_exists('get_user_points') ? get_user_points((int) $current_user->ID) : 0;
    ?>
    <div class="dashboard-grid stats-cards myaccount-points-cards">
        <div class="dashboard-card" data-stat="points">
            <i class="fa-solid fa-coins" aria-hidden="true"></i>
            <h3><?php esc_html_e('Points', 'chassesautresor-com'); ?></h3>
            <p class="stat-value"><?php echo esc_html($points); ?></p>
        </div>
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-coins"></i>
              <h3><?php esc_html_e('Gestion Points', 'chassesautresor-com'); ?></h3>
            </div>
            <div class="stats-content">
                <form method="POST" class="form-gestion-points">
                    <?php wp_nonce_field('gestion_points_action', 'gestion_points_nonce'); ?>
                    <div class="gestion-points-ligne">
                        <label for="utilisateur-points"></label>
                          <input type="text" id="utilisateur-points" placeholder="<?php esc_attr_e('Rechercher un utilisateur...', 'chassesautresor-com'); ?>" required>
                        <input type="hidden" id="utilisateur-id" name="utilisateur">
                        <label for="type-modification"></label>
                        <select id="type-modification" name="type_modification" required>
                            <option value="ajouter">➕</option>
                            <option value="retirer">➖</option>
                        </select>
                    </div>
                    <div class="gestion-points-ligne">
                        <label for="nombre-points"></label>
                          <input type="number" id="nombre-points" name="nombre_points" placeholder="<?php esc_attr_e('nb de points', 'chassesautresor-com'); ?>" min="1" required>
                        <button type="submit" name="modifier_points" class="btn-icon bouton-tertiaire">✅</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="stats-table-wrapper">
          <h3><?php esc_html_e('Historique des conversions', 'chassesautresor-com'); ?></h3>
        <div
            id="historique-paiements-admin"
            class="liste-paiements"
            data-page="<?php echo esc_attr($historique['page']); ?>"
            data-pages="<?php echo esc_attr($historique['pages']); ?>"
        >
            <?php echo $historique['html']; ?>
        </div>
    </div>
    <?php
} elseif ($is_organizer) {
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
          echo '<p class="myaccount-points"><a href="' . $shop_url . '">' . esc_html__('Ajouter des points', 'chassesautresor-com') . '</a></p>';
    }
}

echo render_points_history_table((int) $current_user->ID);
