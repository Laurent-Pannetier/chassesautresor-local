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
                <?php echo get_svg_icon('enigme'); ?> <?php echo esc_html($infos['total_enigmes']); ?> énigme<?php echo ($infos['total_enigmes'] > 1 ? 's' : ''); ?> —
                <?php echo get_svg_icon('participants'); ?><?php echo esc_html($infos['nb_joueurs_label']); ?>
            </div>
            <div class="meta-etiquette">
                <?php echo get_svg_icon('calendar'); ?>
                <span class="chasse-date-plage">
                    <span class="date-debut"><?php echo esc_html($infos['date_debut']); ?></span> →
                    <span class="date-fin"><?php echo esc_html($infos['date_fin']); ?></span>
                </span>
            </div>
        </div>

        <?php echo $infos['extrait_html']; ?>
        <?php echo $infos['lot_html']; ?>
        <div class="cta-chasse-row">
            <div class="cta-action"><?php echo $infos['cta_html']; ?></div>
            <div class="cta-message" aria-live="polite"><?php echo $infos['cta_message']; ?></div>
        </div>
        <?php echo $infos['footer_html']; ?>
    </div>
</div>

