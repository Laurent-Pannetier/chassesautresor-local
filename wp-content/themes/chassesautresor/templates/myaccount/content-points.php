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
                        <button type="submit" name="modifier_points" class="bouton-secondaire">✅</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="stats-table-wrapper">
        <h3><?php esc_html_e('Historique des conversions', 'chassesautresor'); ?></h3>
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
    if (function_exists('charger_script_conversion')) {
        charger_script_conversion(true);
    }

    $user_id         = (int) $current_user->ID;
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
            <h3><?php esc_html_e('Conversion', 'chassesautresor'); ?></h3>
            <button type="button" id="open-conversion-modal" class="stat-value">
                <?php esc_html_e('Convertir', 'chassesautresor-com'); ?>
            </button>
        </div>
        <div class="dashboard-card" data-stat="bank-details">
            <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
            <h3>
                <?php esc_html_e('Coordonnées bancaires', 'chassesautresor-com'); ?>
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

if ($is_organizer) {
    ob_start();
    afficher_tableau_paiements_organisateur((int) $current_user->ID, 'toutes');
    $conversion_table = trim(ob_get_clean());
    if ($conversion_table !== '') {
        global $wpdb;
        $repo            = new PointsRepository($wpdb);
        $paid_requests   = $repo->getConversionRequests((int) $current_user->ID, 'paid');
        $total_points    = 0;
        $total_eur       = 0.0;
        foreach ($paid_requests as $request) {
            $total_points += abs((int) $request['points']);
            $total_eur    += (float) $request['amount_eur'];
        }

        $total_points_label = sprintf(
            '%s : %s',
            esc_html__('Total points', 'chassesautresor'),
            number_format_i18n($total_points)
        );
        $total_eur_label = sprintf(
            '%s : %s €',
            esc_html__('Total €', 'chassesautresor'),
            number_format_i18n($total_eur, 2)
        );

        echo '<div class="stats-table-wrapper">';
        echo '<h3>' . esc_html__('Historique conversion de points', 'chassesautresor') . '</h3>';
        echo '<div class="stats-table-summary">';
        echo '<span class="etiquette etiquette-grande">' . esc_html($total_points_label) . '</span>';
        echo '<span class="etiquette etiquette-grande">' . esc_html($total_eur_label) . '</span>';
        echo '</div>';
        echo $conversion_table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }
}

echo render_points_history_table((int) $current_user->ID);
