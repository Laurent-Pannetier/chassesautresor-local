<?php
/**
 * Template : single-indice.php
 * Affiche le panneau d'édition d'un indice.
 */

defined('ABSPATH') || exit;

$indice_id      = get_the_ID();
$edition_active = utilisateur_peut_modifier_post($indice_id);
$contenu        = get_field('indice_contenu', $indice_id);

if ($edition_active) {
    acf_form_head();
}

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <?php if ($edition_active) : ?>
            <button id="toggle-mode-edition-indice" type="button" class="toggle-mode-edition-indice">
                <?= esc_html__('Paramètres', 'chassesautresor-com'); ?>
            </button>
        <?php endif; ?>

        <article class="indice">
            <h1><?= esc_html(get_the_title($indice_id)); ?></h1>
            <?php if (!empty($contenu)) : ?>
                <div class="indice-contenu">
                    <?= apply_filters('the_content', $contenu); ?>
                </div>
            <?php endif; ?>
        </article>

        <?php
        get_template_part('template-parts/indice/indice-edition-main', null, [
            'indice_id' => $indice_id,
        ]);
        ?>
    </main>
</div>

<?php get_footer(); ?>
