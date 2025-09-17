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

        <?php
        get_template_part(
            'template-parts/chasse/partials/chasse-meta-row',
            null,
            array(
                'infos'           => $infos,
                'wrapper_class'   => 'meta-row svg-xsmall',
                'display_mode'    => 'buttons',
                'use_short_dates' => true,
            )
        );
        ?>

        <?php echo $infos['extrait_html']; ?>
        <?php echo $infos['lot_html']; ?>
        <div class="flex-row cta-div">
            <a href="<?php echo esc_url($infos['permalink']); ?>" class="bouton-secondaire"><?= esc_html__('En savoir plus', 'chassesautresor-com'); ?></a>
        </div>
        <?php echo $infos['footer_html']; ?>
    </div>
</div>

