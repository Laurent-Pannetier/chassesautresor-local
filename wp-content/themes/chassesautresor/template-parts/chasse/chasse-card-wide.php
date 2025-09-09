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
        <?php
        echo wp_get_attachment_image(
            $infos['image_id'],
            'large',
            false,
            [ 'sizes' => '(min-width:768px) 100vw, 100vw' ]
        );
        ?>
    </div>

    <div class="carte-ligne__contenu">
        <h3 class="carte-ligne__titre">
            <a href="<?php echo esc_url($infos['permalink']); ?>"><?php echo esc_html($infos['titre']); ?></a>
        </h3>

        <div class="meta-row svg-xsmall">
            <div class="meta-regular">
                <?php echo get_svg_icon('enigme'); ?>
                <?php echo esc_html(sprintf(_n('%d énigme', '%d énigmes', $infos['total_enigmes'], 'chassesautresor-com'), $infos['total_enigmes'])); ?> —
                <?php echo get_svg_icon('participants'); ?><?php echo esc_html($infos['nb_joueurs_label']); ?>
            </div>
            <div class="meta-etiquette">
                <?php echo get_svg_icon('calendar'); ?>
                <span class="chasse-date-plage">
                    <span class="date-debut"><?php echo esc_html($infos['date_debut']); ?></span> –
                    <span class="date-fin"><?php echo esc_html($infos['date_fin']); ?></span>
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

