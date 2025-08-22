<?php
/**
 * Template Part: Panneau d'édition frontale d'un indice
 * Requiert : $args['indice_id']
 */

defined('ABSPATH') || exit;

$indice_id = $args['indice_id'] ?? null;
if (!$indice_id || get_post_type($indice_id) !== 'indice') {
    return;
}

$peut_modifier = utilisateur_peut_voir_panneau($indice_id);
if (!$peut_modifier) {
    return;
}

$titre = get_the_title($indice_id);
?>

<section class="edition-panel edition-panel-indice edition-panel-modal" data-cpt="indice" data-post-id="<?= esc_attr($indice_id); ?>">
    <div class="edition-panel-header">
        <h2>
            <i class="fa-solid fa-gear"></i>
            <?= esc_html__('Panneau d\'édition indice', 'chassesautresor-com'); ?> :
            <span class="titre-objet" data-cpt="indice"><?= esc_html($titre); ?></span>
        </h2>
        <button type="button" class="panneau-fermer" aria-label="<?= esc_attr__('Fermer les paramètres', 'chassesautresor-com'); ?>">✖</button>
    </div>

    <div class="edition-panel-body">
        <p><?= esc_html__('Contenu d\'édition à venir...', 'chassesautresor-com'); ?></p>
    </div>
</section>
