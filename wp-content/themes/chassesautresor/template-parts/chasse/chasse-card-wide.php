<?php
defined('ABSPATH') || exit;

if (!isset($args['chasse_id']) || empty($args['chasse_id'])) {
    return;
}

$chasse_id = (int) $args['chasse_id'];
$completion_class = $args['completion_class'] ?? '';
$infos           = preparer_infos_affichage_carte_chasse($chasse_id);

$orga_id    = get_organisateur_from_chasse($chasse_id);
$logo_url   = $orga_id ? get_the_post_thumbnail_url($orga_id, 'thumbnail') : '';
$orga_title = $orga_id ? get_the_title($orga_id) : '';
$orga_link  = $orga_id ? get_permalink($orga_id) : '';

if (empty($infos)) {
    return;
}
?>
<div class="carte carte-chasse carte-wide <?php echo esc_attr(trim($infos['classe_statut'] . ' ' . $completion_class)); ?>">
    <div class="carte-wide__image">
        <span class="badge-statut <?php echo esc_attr($infos['badge_class']); ?>"
            data-post-id="<?php echo esc_attr($chasse_id); ?>">
            <?php echo esc_html($infos['statut_label']); ?>
        </span>
        <?php if ($infos['mode_validation'] === 'manuelle' || $infos['mode_validation'] === 'automatique') : ?>
            <?php
            $title_mode = $infos['mode_validation'] === 'automatique'
                ? __('mode de fin de chasse : automatique', 'chassesautresor-com')
                : __('mode de fin de chasse : manuelle', 'chassesautresor-com');
            ?>
            <span class="mode-fin-icone" title="<?php echo esc_attr($title_mode); ?>" aria-label="<?php echo esc_attr($title_mode); ?>">
                <?php if ($infos['mode_validation'] === 'automatique') : ?>
                    <i class="fa-solid fa-bolt"></i>
                <?php else : ?>
                    <?php echo get_svg_icon('hand'); ?>
                <?php endif; ?>
            </span>
        <?php endif; ?>
        <img src="<?php echo esc_url($infos['image']); ?>" alt="<?php echo esc_attr($infos['titre']); ?>">
    </div>

    <div class="carte-wide__contenu">
        <div class="carte-wide__header">
            <?php if ($orga_id && $logo_url) : ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($orga_title); ?>">
                <a href="<?php echo esc_url($orga_link); ?>"><?php echo esc_html($orga_title); ?></a>
                <?php echo esc_html__('présente', 'chassesautresor-com'); ?>
            <?php endif; ?>

            <h3 class="carte-wide__titre">
                <a href="<?php echo esc_url($infos['permalink']); ?>"><?php echo esc_html($infos['titre']); ?></a>
            </h3>
        </div>

        <div class="carte-wide__content">
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

            <?php if ((int) $infos['cout_points'] > 0) : ?>
            <div class="meta-badges">
                <span class="badge-rond badge-cout"
                    aria-label="<?php echo esc_attr(
                        sprintf(
                            esc_html__('Coût par tentative : %d points.', 'chassesautresor-com'),
                            $infos['cout_points']
                        )
                    ); ?>">
                    <?php echo get_svg_icon('coins-points'); ?>
                    <span><?php echo esc_html($infos['cout_points']); ?></span>
                </span>
            </div>
            <?php endif; ?>
            <?php echo $infos['extrait_html']; ?>
            <?php echo $infos['lot_html']; ?>
        </div>

        <div class="carte-wide__footer">
            <div class="flex-row cta-div">
                <a href="<?php echo esc_url($infos['permalink']); ?>" class="bouton-secondaire">
                    <?php echo esc_html__('En savoir plus', 'chassesautresor-com'); ?>
                </a>
            </div>
            <?php echo $infos['footer_html']; ?>
        </div>
    </div>
</div>
