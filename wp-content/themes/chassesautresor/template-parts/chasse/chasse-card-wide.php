<?php
defined('ABSPATH') || exit;

if (!isset($args['chasse_id']) || empty($args['chasse_id'])) {
    return;
}

$chasse_id = (int) $args['chasse_id'];
$infos     = preparer_infos_affichage_carte_chasse($chasse_id);

if (empty($infos)) {
    return;
}
?>
<div class="carte carte-chasse carte-wide <?php echo esc_attr($infos['classe_statut']); ?>">
    <div class="carte-wide__image">
        <span class="badge-statut <?php echo esc_attr($infos['badge_class']); ?>" data-post-id="<?php echo esc_attr($chasse_id); ?>">
            <?php echo esc_html($infos['statut_label']); ?>
        </span>
        <img src="<?php echo esc_url($infos['image']); ?>" alt="<?php echo esc_attr($infos['titre']); ?>">
    </div>

    <div class="carte-wide__contenu">
        <h3 class="carte-wide__titre">
            <a href="<?php echo esc_url($infos['permalink']); ?>"><?php echo esc_html($infos['titre']); ?></a>
        </h3>

        <div class="meta-row svg-xsmall">
            <div class="meta-regular">
                <?php echo get_svg_icon('enigme'); ?>
                <?php
                echo esc_html(
                    sprintf(
                        _n('%d énigme', '%d énigmes', $infos['total_enigmes'], 'chassesautresor-com'),
                        $infos['total_enigmes']
                    )
                );
                ?> —
                <?php echo get_svg_icon('participants'); ?><?php echo esc_html($infos['nb_joueurs_label']); ?>
            </div>
        </div>

        <?php if ((int) $infos['cout_points'] > 0 || $infos['mode_validation'] !== '') : ?>
        <div class="meta-badges">
            <?php if ((int) $infos['cout_points'] > 0) : ?>
            <span class="badge-rond badge-cout" aria-label="<?php echo esc_attr(sprintf(esc_html__('Coût par tentative : %d points.', 'chassesautresor-com'), $infos['cout_points'])); ?>">
                <?php echo get_svg_icon('coins-points'); ?>
                <span><?php echo esc_html($infos['cout_points']); ?></span>
            </span>
            <?php endif; ?>

            <?php if ($infos['mode_validation'] === 'manuelle') : ?>
            <span class="badge-rond badge-validation" aria-label="<?php echo esc_attr(esc_html__('Validation manuelle', 'chassesautresor-com')); ?>">
                <?php echo get_svg_icon('reply-mail'); ?>
            </span>
            <?php elseif ($infos['mode_validation'] === 'automatique') : ?>
            <span class="badge-rond badge-validation" aria-label="<?php echo esc_attr(esc_html__('Validation automatique', 'chassesautresor-com')); ?>">
                <?php echo get_svg_icon('reply-auto'); ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php echo $infos['extrait_html']; ?>
        <?php echo $infos['lot_html']; ?>
        <div class="flex-row cta-div">
            <a href="<?php echo esc_url($infos['permalink']); ?>" class="bouton-secondaire"><?php echo esc_html__('En savoir plus', 'chassesautresor-com'); ?></a>
        </div>
        <?php echo $infos['footer_html']; ?>
    </div>
</div>
