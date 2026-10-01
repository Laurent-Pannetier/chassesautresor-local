<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

/** Render the points conversion modal without a theme template. */
final class ConversionModalRenderer
{
    /** @param bool|string $access */
    public function render($access, int $organizerId, int $minimum, int $balance, float $rate): string
    {
        ob_start();
        echo '<span class="close-modal">&times;</span>';

        if ($access === 'INSUFFICIENT_POINTS') {
            $this->renderInsufficientBalance($minimum);
        } elseif ($access === 'MISSING_BANK_DETAILS') {
            $this->renderMissingBankDetails($organizerId);
        } elseif (is_string($access) && $access !== '') {
            echo '<p>' . esc_html($access) . '</p>';
        } else {
            $this->renderForm($minimum, $balance, $rate);
        }

        return (string) ob_get_clean();
    }

    private function renderInsufficientBalance(int $minimum): void
    {
        ?>
        <div class="points-modal-message">
            <i class="fa-solid fa-circle-exclamation modal-icon" aria-hidden="true"></i>
            <h2><?= esc_html__('Solde insuffisant', 'chassesautresor-com'); ?></h2>
            <p><?= esc_html(sprintf(
                __('Conversion possible à partir de %d points', 'chassesautresor-com'),
                $minimum
            )); ?></p>
            <button type="button" class="close-modal"><?= esc_html__('Fermer', 'chassesautresor-com'); ?></button>
        </div>
        <?php
    }

    private function renderMissingBankDetails(int $organizerId): void
    {
        ?>
        <div class="points-modal-message">
            <i class="fa-solid fa-building-columns modal-icon" aria-hidden="true"></i>
            <h2><?= esc_html__('Coordonnées bancaires manquantes', 'chassesautresor-com'); ?></h2>
            <p><?= esc_html__(
                "Nous avons besoin d'enregistrer vos coordonnées bancaires pour vous envoyer un versement",
                'chassesautresor-com'
            ); ?></p>
            <p><a id="ouvrir-coordonnees-modal" class="champ-modifier" href="#"
                aria-label="<?= esc_attr__('Ajouter des coordonnées bancaires', 'chassesautresor-com'); ?>"
                data-champ="coordonnees_bancaires" data-cpt="organisateur"
                data-post-id="<?= esc_attr($organizerId); ?>"
                data-label-add="<?= esc_attr__('Ajouter', 'chassesautresor-com'); ?>"
                data-label-edit="<?= esc_attr__('Éditer', 'chassesautresor-com'); ?>"
                data-aria-add="<?= esc_attr__('Ajouter des coordonnées bancaires', 'chassesautresor-com'); ?>"
                data-aria-edit="<?= esc_attr__('Modifier les coordonnées bancaires', 'chassesautresor-com'); ?>"
            ><?= esc_html__('Renseigner les coordonnées bancaires', 'chassesautresor-com'); ?></a></p>
            <button type="button" class="close-modal"><?= esc_html__('Fermer', 'chassesautresor-com'); ?></button>
        </div>
        <?php
    }

    private function renderForm(int $minimum, int $balance, float $rate): void
    {
        ?>
        <span class="conversion-rate-badge">
            <?= esc_html(sprintf(__('1 000 points = %s €', 'chassesautresor-com'), $rate)); ?>
        </span>
        <i class="fa-solid fa-right-left modal-top-icon" aria-hidden="true"></i>
        <h2 class="modal-title"><?= esc_html__('Demande de conversion', 'chassesautresor-com'); ?></h2>
        <p class="modal-description">
            <?= esc_html(sprintf(__('Transformez vos %d points en euros.', 'chassesautresor-com'), $balance)); ?>
        </p>
        <form action="" method="POST">
            <div class="conversion-row">
                <label for="points-a-convertir"><?= esc_html__('Convertir', 'chassesautresor-com'); ?></label>
                <input type="number" name="points_a_convertir" id="points-a-convertir"
                    min="<?= esc_attr($minimum); ?>" max="<?= esc_attr($balance); ?>" step="1" value=""
                    data-taux="<?= esc_attr($rate); ?>">
                <span class="points-unit"><?= esc_html__('points', 'chassesautresor-com'); ?></span>
            </div>
            <p class="conversion-equivalent">
                <span class="label"><?= esc_html__('contre valeur', 'chassesautresor-com'); ?></span>
                <span class="amount"><span id="montant-equivalent">0.00</span> €</span>
            </p>
            <input type="hidden" name="demander_paiement" value="1">
            <?php wp_nonce_field('demande_paiement_action', 'demande_paiement_nonce'); ?>
            <div class="modal-actions">
                <button type="submit" disabled><?= esc_html__('Convertir', 'chassesautresor-com'); ?></button>
            </div>
        </form>
        <?php
    }
}
