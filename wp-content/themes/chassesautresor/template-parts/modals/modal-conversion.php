<?php
defined('ABSPATH') || exit;

$access_message = verifier_acces_conversion(get_current_user_id());
?>
<div id="conversion-modal" class="points-modal">
    <div class="points-modal-content">
        <span class="close-modal">&times;</span>
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
        <?php if (is_string($access_message) && $access_message !== '') : ?>
            <p><?php echo wp_kses(
                    $access_message,
                    [
                        'a' => [
                            'id'            => [],
                            'class'         => [],
                            'href'          => [],
                            'aria-label'    => [],
                            'data-champ'    => [],
                            'data-cpt'      => [],
                            'data-post-id'  => [],
                            'data-label-add'    => [],
                            'data-label-edit'   => [],
                            'data-aria-add'     => [],
                            'data-aria-edit'    => [],
                        ],
                    ]
                ); ?></p>
        <?php else : ?>
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
