<?php
/**
 * Template Part: Indice - Affichage
 * Displays the title, image and content of an indice.
 */

defined('ABSPATH') || exit;

$indice_id = $args['indice_id'] ?? null;
if (! $indice_id || get_post_type($indice_id) !== 'indice') {
    return;
}

$titre = get_the_title($indice_id);
$image_url = get_the_post_thumbnail_url($indice_id, 'large');
$contenu = apply_filters('the_content', get_post_field('post_content', $indice_id));
$edition_active = utilisateur_peut_modifier_post($indice_id);
?>

<section class="indice-affichage" data-post-id="<?= esc_attr($indice_id); ?>">
    <?php if ($edition_active) : ?>
        <button id="toggle-mode-edition-indice" type="button" class="bouton-edition-toggle" data-cpt="indice" aria-label="<?= esc_attr__('Activer Orgy', 'chassesautresor-com'); ?>">
            <i class="fa-solid fa-gear"></i>
        </button>
    <?php endif; ?>

    <h1 class="titre-indice" data-cpt="indice" data-post-id="<?= esc_attr($indice_id); ?>">
        <?= esc_html($titre); ?>
    </h1>

    <?php if ($image_url) : ?>
        <div class="indice-image">
            <img src="<?= esc_url($image_url); ?>" alt="<?= esc_attr__('Image de l\'indice', 'chassesautresor-com'); ?>" />
        </div>
    <?php endif; ?>

    <div class="indice-contenu">
        <?= $contenu; ?>
    </div>
</section>
