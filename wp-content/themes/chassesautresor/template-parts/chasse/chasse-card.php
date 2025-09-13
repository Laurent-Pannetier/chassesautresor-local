<?php
defined('ABSPATH') || exit;

if (!isset($args['chasse_id']) || empty($args['chasse_id'])) {
    return;
}

$chasse_id = $args['chasse_id'];
$completion_class = $args['completion_class'] ?? '';

$infos = preparer_infos_affichage_carte_chasse($chasse_id);
if (empty($infos)) {
    return;
}
?>

<div class="carte carte-ligne carte-chasse <?php echo esc_attr(trim($infos['classe_statut'] . ' ' . $completion_class)); ?>">
    <div class="carte-ligne__image">
        <span class="badge-statut <?php echo esc_attr($infos['badge_class']); ?>" data-post-id="<?php echo esc_attr($chasse_id); ?>">
            <?php echo esc_html($infos['statut_label']); ?>
        </span>
        <img src="<?php echo esc_url($infos['image']); ?>" alt="<?php echo esc_attr($infos['titre']); ?>">
    </div>

    <div class="carte-ligne__contenu">
        <h3 class="carte-ligne__titre">
            <a href="<?php echo esc_url($infos['permalink']); ?>"><?php echo esc_html($infos['titre']); ?></a>
        </h3>

        <div class="meta-row svg-xsmall">
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

        <?php echo $infos['extrait_html']; ?>
        <?php echo $infos['lot_html']; ?>
        <div class="flex-row cta-div">
            <a href="<?php echo esc_url($infos['permalink']); ?>" class="bouton-secondaire"><?= esc_html__('En savoir plus', 'chassesautresor-com'); ?></a>
        </div>
        <?php echo $infos['footer_html']; ?>
    </div>
</div>

