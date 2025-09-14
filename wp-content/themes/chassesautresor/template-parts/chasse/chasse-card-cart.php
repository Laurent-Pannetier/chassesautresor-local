<?php
/**
 * Compact card format for hunts (CART).
 * Image on top, title, and meta footer.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

if (!isset($args['chasse_id']) || empty($args['chasse_id'])) {
    return;
}

$chasse_id       = (int) $args['chasse_id'];
$completion_class = $args['completion_class'] ?? '';
$infos           = preparer_infos_affichage_carte_chasse($chasse_id);

if (empty($infos)) {
    return;
}
?>
<div class="carte carte-chasse carte-cart <?php echo esc_attr(trim($infos['classe_statut'] . ' ' . $completion_class)); ?>">
    <a href="<?php echo esc_url($infos['permalink']); ?>" class="carte-cart__lien">
        <div class="carte-cart__image-wrapper">
            <span class="badge-statut <?php echo esc_attr($infos['badge_class']); ?>" data-post-id="<?php echo esc_attr($chasse_id); ?>">
                <?php echo esc_html($infos['statut_label']); ?>
            </span>
            <img src="<?php echo esc_url($infos['image']); ?>" alt="<?php echo esc_attr($infos['titre']); ?>" class="carte-cart__image">
        </div>
        <div class="carte-cart__contenu">
            <h3 class="carte-cart__titre"><?php echo esc_html($infos['titre']); ?></h3>
            <?php echo $infos['lot_html']; ?>
        </div>
    </a>
    <div class="carte-cart__footer meta-row svg-xsmall">
        <div class="meta-regular">
            <button type="button" class="meta-indic" data-tap="<?= esc_attr__('nombre d\'énigmes', 'chassesautresor-com'); ?>">
                <?php echo get_svg_icon('enigme'); ?>
                <span class="meta-indic__count"><?php echo esc_html(number_format_i18n($infos['total_enigmes'])); ?></span>
            </button>
            <button type="button" class="meta-indic" data-tap="<?= esc_attr__('nombre de joueurs', 'chassesautresor-com'); ?>">
                <?php echo get_svg_icon('participants'); ?>
                <span class="meta-indic__count"><?php echo esc_html(number_format_i18n($infos['nb_joueurs'])); ?></span>
            </button>
        </div>
        <div class="meta-etiquette">
            <?php echo get_svg_icon('calendar'); ?>
            <span class="chasse-date-plage">
                <span class="date-debut"><?php echo esc_html($infos['date_debut_court']); ?></span> –
                <span class="date-fin"><?php echo esc_html($infos['date_fin_court']); ?></span>
            </span>
        </div>
    </div>
</div>
