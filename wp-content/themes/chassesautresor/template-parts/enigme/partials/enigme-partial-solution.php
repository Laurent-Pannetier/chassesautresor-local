<?php
defined('ABSPATH') || exit;

$post_id = $args['post_id'] ?? null;
if (!$post_id) {
    return;
}

$mode       = get_field('enigme_solution_mode', $post_id) ?? 'pdf';
$texte      = get_field('enigme_solution_explication', $post_id);
$fichier    = get_field('enigme_solution_fichier', $post_id);
$fichier_url = is_array($fichier) ? $fichier['url'] ?? '' : '';
$fichier_nom = is_array($fichier) ? $fichier['filename'] ?? basename($fichier_url) : basename($fichier_url);

if (!solution_peut_etre_affichee($post_id)) {
    return;
}

if ($mode === 'pdf' && $fichier_url) {
    echo '<a href="' . esc_url($fichier_url) . '" class="lien-solution-pdf" target="_blank" rel="noopener">';
    echo '&#128196; ' . esc_html($fichier_nom);
    echo '</a>';
    return;
}

if ($mode === 'texte' && $texte) {
    echo '<p>' . wp_kses_post($texte) . '</p>';
}
?>
