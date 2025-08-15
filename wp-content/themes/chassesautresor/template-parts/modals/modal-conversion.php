<?php
defined('ABSPATH') || exit;

$access_message = verifier_acces_conversion(get_current_user_id());
?>
<div id="conversion-modal" class="points-modal">
    <div class="points-modal-content">
        <span class="close-modal">&times;</span>
        <?php if ($access_message === 'INSUFFICIENT_POINTS') : ?>
            <div class="points-modal-message">
                <i class="fa-solid fa-circle-exclamation modal-icon" aria-hidden="true"></i>
                <h2>solde insuffisant</h2>
                <p>Conversion possible à partir de 500 points</p>
                <button type="button" class="close-modal">Fermer</button>
            </div>
        <?php elseif ($access_message === 'MISSING_BANK_DETAILS') : ?>
            <div class="points-modal-message">
                <i class="fa-solid fa-building-columns modal-icon" aria-hidden="true"></i>
                <h2>Coordonnées bancaires manquantes</h2>
                <p>nous avons besoin d'enregistrer vos coordonnées bancaires pour vous envoyer un versement</p>
                <button type="button" class="close-modal">Fermer</button>
            </div>
        <?php elseif (is_string($access_message) && $access_message !== '') : ?>
            <p><?php echo esc_html($access_message); ?></p>
        <?php else : ?>
            <h2>💰 Taux de conversion</h2>
            <p>1 000 points = <?php echo esc_html(get_taux_conversion_actuel()); ?> €</p>
            <p>
                La conversion des points en € n'est possible qu'à partir de 500 points
                afin d'éviter les mico-paiements qui génèrent des frais fixes
            </p>
            <p>
                Ce taux est fixé par chassesautresor.com et peut être modifié :
                vous serez toujours prévenu préalablement avant toute éventuelle
                modification
            </p>
            <form action="" method="POST">
                <label for="points-a-convertir">Points à convertir</label>
                <input type="number"
                    name="points_a_convertir"
                    id="points-a-convertir"
                    min="500"
                    max="<?php echo esc_attr(get_user_points()); ?>"
                    data-taux="<?php echo esc_attr(get_taux_conversion_actuel()); ?>">
                <input type="hidden" name="demander_paiement" value="1">
                <?php wp_nonce_field('demande_paiement_action', 'demande_paiement_nonce'); ?>
                <button type="submit"><?php esc_html_e('Envoyer', 'chassesautresor-com'); ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>
