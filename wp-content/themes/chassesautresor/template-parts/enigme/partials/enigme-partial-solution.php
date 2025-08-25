<?php
defined('ABSPATH') || exit;

$post_id = $args['post_id'] ?? null;
if (!$post_id) {
    return;
}

$solution = solution_recuperer_par_objet($post_id, 'enigme');
if (!solution_peut_etre_affichee($post_id) || !$solution) {
    return;
}

$fichier     = get_field('solution_fichier', $solution->ID);
$fichier_url = is_array($fichier) ? ($fichier['url'] ?? '') : '';
$fichier_nom = is_array($fichier) ? ($fichier['filename'] ?? basename($fichier_url)) : basename($fichier_url);
$texte       = get_field('solution_explication', $solution->ID);

if ($fichier_url) {
    echo '<a href="' . esc_url($fichier_url) . '" class="lien-solution-pdf" target="_blank" rel="noopener">';
    echo '&#128196; ' . esc_html($fichier_nom);
    echo '</a>';
    return;
}

if ($texte) {
    echo '<p>' . wp_kses_post($texte) . '</p>';
}
?>
