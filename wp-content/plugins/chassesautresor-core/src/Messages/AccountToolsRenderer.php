<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

use ChassesAuTresor\Core\Points\ConversionSettingsService;
use Closure;

/** Render the administrator tools section without a theme template dependency. */
final class AccountToolsRenderer {
    private Closure $rateProvider;
    private Closure $protectionProvider;

    public function __construct(?callable $rateProvider = null, ?callable $protectionProvider = null) {
        $this->rateProvider = Closure::fromCallable(
            $rateProvider ?? static fn (): float => (new ConversionSettingsService())->getRate()
        );
        $this->protectionProvider = Closure::fromCallable(
            $protectionProvider ?? static fn (): bool => get_option('ca_site_password_enabled', '1') === '1'
        );
    }

    public function render(): string {
        $rate = (float) ($this->rateProvider)();
        $protectionActive = (bool) ($this->protectionProvider)();
        $pointsUiEnabled = !function_exists('cat_is_points_ui_enabled') || cat_is_points_ui_enabled();
        ob_start();
        ?>
<section>
    <h1 class="mb-4 text-xl font-semibold"><?php esc_html_e('Outils', 'chassesautresor-com'); ?></h1>
    <div class="dashboard-grid">
        <?php if ($pointsUiEnabled) : ?>
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
                        <input
                            type="text"
                            id="utilisateur-points"
                            placeholder="<?php esc_attr_e('Rechercher un utilisateur...', 'chassesautresor-com'); ?>"
                            required
                        >
                        <input type="hidden" id="utilisateur-id" name="utilisateur">
                        <label for="type-modification"></label>
                        <select id="type-modification" name="type_modification" required>
                            <option value="ajouter">➕</option>
                            <option value="retirer">➖</option>
                        </select>
                    </div>
                    <div class="gestion-points-ligne">
                        <label for="nombre-points"></label>
                        <input
                            type="number"
                            id="nombre-points"
                            name="nombre_points"
                            placeholder="<?php esc_attr_e('Nombre de points', 'chassesautresor-com'); ?>"
                            min="1"
                            required
                        >
                        <button type="submit" name="modifier_points" class="btn-icon bouton-tertiaire">✅</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        <?= $this->renderProtectionCard($protectionActive); ?>
        <?php if ($pointsUiEnabled) : ?>
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-euro-sign"></i>
                <h3><?php esc_html_e('Taux Conversion', 'chassesautresor-com'); ?></h3>
            </div>
            <div class="stats-content">
                <p>1 000 points = <strong><?php echo esc_html($rate); ?> €</strong>
                    <span class="conversion-info">
                        <i class="fas fa-info-circle" id="open-taux-modal"></i>
                    </span>
                </p>
                    <div class="overlay-taux">
                        <button class="bouton-secondaire" id="modifier-taux">
                            <?php esc_html_e('modifier', 'chassesautresor-com'); ?>
                        </button>
                    </div>
                    <form method="POST" class="form-taux-conversion" id="form-taux-conversion" style="display: none;">
                        <?php wp_nonce_field('modifier_taux_conversion_action', 'modifier_taux_conversion_nonce'); ?>
                        <label for="nouveau-taux">
                            <?php esc_html_e('Définir un nouveau taux :', 'chassesautresor-com'); ?>
                        </label>
                        <input
                            type="number"
                            name="nouveau_taux"
                            id="nouveau-taux"
                            step="0.01"
                            min="0"
                            value="<?php echo esc_attr($rate); ?>"
                            required
                        >
                        <button type="submit" name="enregistrer_taux" class="bouton-secondaire">
                            <?php esc_html_e('Mettre à jour', 'chassesautresor-com'); ?>
                        </button>
                    </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="dashboard-card">
            <i class="fas fa-tools"></i>
            <h3><?php esc_html_e('ACF', 'chassesautresor-com'); ?></h3>
            <p class="stat-value">
                <button id="afficher-champs-acf" class="bouton-tertiaire">
                    <?php esc_html_e('Afficher les champs ACF', 'chassesautresor-com'); ?>
                </button>
            </p>
            <div class="stats-content">
                <div id="acf-fields-container" style="display:none;margin-top:10px;">
                    <textarea id="acf-fields-output" style="width:100%;height:300px;" readonly></textarea>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-undo"></i>
                <h3><?php esc_html_e('Reset stats', 'chassesautresor-com'); ?></h3>
            </div>
            <div class="stats-content">
                <button id="reset-stats-btn" class="btn-danger" data-reset-stats>
                    <?php esc_html_e('Effacer', 'chassesautresor-com'); ?>
                </button>
            </div>
        </div>
    </div>
</section>

        <?php
        return trim((string) ob_get_clean());
    }

    public function renderProtectionCard(?bool $protectionActive = null): string
    {
        $isActive = $protectionActive ?? (bool) ($this->protectionProvider)();
        ob_start();
        ?>
        <div class="dashboard-card site-protection-card">
            <div class="dashboard-card-header">
                <i class="fas fa-lock" aria-hidden="true"></i>
                <h3><?php esc_html_e('Protection globale', 'chassesautresor-com'); ?></h3>
            </div>
            <div class="dashboard-card-content stats-content">
                <label class="switch-control">
                    <input type="checkbox" id="site-protection-toggle" <?php checked($isActive); ?>>
                    <span class="switch-slider"></span>
                </label>
                <span id="site-protection-status">
                    <?php
                    echo $isActive
                        ? esc_html__('Activé', 'chassesautresor-com')
                        : esc_html__('Désactivé', 'chassesautresor-com');
                    ?>
                </span>
            </div>
        </div>
        <?php

        return trim((string) ob_get_clean());
    }
}
