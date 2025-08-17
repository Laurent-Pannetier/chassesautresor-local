<?php
/**
 * Dynamic content for the "Outils" section.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

if (!current_user_can('administrator')) {
    wp_redirect(home_url('/mon-compte/'));
    exit;
}

$taux_conversion = get_taux_conversion_actuel();
?>
<section>
    <h1 class="mb-4 text-xl font-semibold"><?php esc_html_e('Outils', 'chassesautresor'); ?></h1>
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-euro-sign"></i>
                <h3><?php esc_html_e('Taux Conversion', 'chassesautresor'); ?></h3>
            </div>
            <div class="stats-content">
                <p>1 000 points = <strong><?php echo esc_html($taux_conversion); ?> €</strong>
                    <span class="conversion-info">
                        <i class="fas fa-info-circle" id="open-taux-modal"></i>
                    </span>
                </p>
                <?php if (current_user_can('administrator')) : ?>
                    <div class="overlay-taux">
                        <button class="bouton-secondaire" id="modifier-taux"><?php esc_html_e('Modifier', 'chassesautresor'); ?></button>
                    </div>
                    <form method="POST" class="form-taux-conversion" id="form-taux-conversion" style="display: none;">
                        <?php wp_nonce_field('modifier_taux_conversion_action', 'modifier_taux_conversion_nonce'); ?>
                        <label for="nouveau-taux"><?php esc_html_e('Définir un nouveau taux :', 'chassesautresor'); ?></label>
                        <input type="number" name="nouveau_taux" id="nouveau-taux" step="0.01" min="0" value="<?php echo esc_attr($taux_conversion); ?>" required>
                        <button type="submit" name="enregistrer_taux" class="bouton-secondaire"><?php esc_html_e('Mettre à jour', 'chassesautresor'); ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-tools"></i>
                <h3>ACF</h3>
            </div>
            <div class="stats-content">
                <button id="afficher-champs-acf" class="bouton-tertiaire"><?php esc_html_e('Afficher les champs ACF', 'chassesautresor'); ?></button>
                <div id="acf-fields-container" style="display:none;margin-top:10px;">
                    <textarea id="acf-fields-output" style="width:100%;height:300px;" readonly></textarea>
                </div>
            </div>
        </div>
    </div>
</section>
