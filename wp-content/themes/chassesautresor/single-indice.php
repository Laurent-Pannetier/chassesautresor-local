<?php
/**
 * Template : single-indice.php
 * Affiche le panneau d'édition d'un indice.
 */

defined('ABSPATH') || exit;

$indice_id = get_the_ID();
$edition_active = utilisateur_peut_modifier_post($indice_id);

if ($edition_active) {
    acf_form_head();
}

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <?php
        get_template_part('template-parts/indice/indice-edition-main', null, [
            'indice_id' => $indice_id,
        ]);
        ?>
    </main>
</div>

<?php get_footer(); ?>
