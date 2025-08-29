<?php

/**
 * Partial : chasse-partial-boucle-enigmes.php
 * Affiche la grille des énigmes d'une chasse (carte par carte).
 */

defined('ABSPATH') || exit;

$chasse_id = $args['chasse_id'] ?? null;
if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') return;

$infos_chasse = $args['infos_chasse'] ?? preparer_infos_affichage_chasse($chasse_id);

$utilisateur_id = get_current_user_id();

// 🔒 Vérification d'accès à la chasse
if (!chasse_est_visible_pour_utilisateur($chasse_id, $utilisateur_id)) return;

$est_orga_associe = $args['est_orga_associe']
    ?? utilisateur_est_organisateur_associe_a_chasse($utilisateur_id, $chasse_id);
$needs_validatable_message = $args['needs_validatable_message'] ?? false;

$autorise_boucle = (
  user_can($utilisateur_id, 'manage_options') ||
  $est_orga_associe ||
  utilisateur_est_engage_dans_chasse($utilisateur_id, $chasse_id)
);
if (!$autorise_boucle) return;

// 🔁 Récupération des énigmes associées
$posts = get_posts([
  'post_type'      => 'enigme',
  'posts_per_page' => -1,
  'orderby'        => 'menu_order',
  'order'          => 'ASC',
  'post_status'    => ['publish', 'pending', 'draft'],
  'meta_query'     => [[
    'key'     => 'enigme_chasse_associee',
    'value'   => $chasse_id,     // 👈 pas de guillemets !
    'compare' => 'LIKE',
  ]]
]);

$posts_visibles = $posts;
$has_enigmes = !empty($posts_visibles);

$est_orga = est_organisateur();
$statut_chasse = get_post_status($chasse_id);

// 📌 Vérifie si une énigme est incomplète
$has_incomplete = false;
foreach ($posts as $p) {
  verifier_ou_mettre_a_jour_cache_complet($p->ID);
  if (!get_field('enigme_cache_complet', $p->ID)) {
    $has_incomplete = true;
    break;
  }
}
?>

<div class="bloc-enigmes-chasse">
  <div class="cards-grid">
    <?php foreach ($posts_visibles as $post):
      $enigme_id = $post->ID;
      $titre = get_the_title($enigme_id);
      $cta = get_cta_enigme($enigme_id, $utilisateur_id);
      $type_cta = $cta['type'] ?? 'inconnu';
      $classe_cta = 'cta-' . sanitize_html_class($type_cta);

      // 🔍 Vérification bordure admin/orga
      $statut_enigme = get_post_status($enigme_id);
      $voir_bordure = $est_orga &&
        $est_orga_associe &&
        $statut_chasse !== 'publish' &&
        $statut_enigme !== 'publish';

      $classe_completion = '';
      if ($voir_bordure) {
        verifier_ou_mettre_a_jour_cache_complet($enigme_id);
        $complet = (bool) get_field('enigme_cache_complet', $enigme_id);
        $classe_completion = $complet ? 'carte-complete' : 'carte-incomplete';
      }

      $classes_carte = trim("carte carte-enigme $classe_completion $classe_cta");
      $mapping_visuel = get_mapping_visuel_enigme($enigme_id);
    ?>
      <article class="<?= esc_attr($classes_carte); ?>">
        <div class="carte-core">
          <div class="carte-enigme-image <?= esc_attr($mapping_visuel['filtre'] ?? ''); ?>"
            title="<?= esc_attr($mapping_visuel['sens'] ?? '') ?>">
            <?php if ($mapping_visuel['image_reelle']) : ?>
              <?php afficher_picture_vignette_enigme($enigme_id, 'Vignette de l’énigme', ['medium']); ?>
            <?php else : ?>
              <div class="enigme-placeholder">
                <?php
                $svg = $mapping_visuel['fallback_svg'] ?? 'warning.svg';
                $svg_path = get_stylesheet_directory() . '/assets/svg/' . $svg;
                if (file_exists($svg_path)) {
                  echo file_get_contents($svg_path);
                } else {
                  echo '<div class="svg-manquant">🕳</div>';
                }
                ?>
              </div>
            <?php endif; ?>
            <?php if (!in_array($cta['type'], ['bloquee', 'invalide', 'cache_invalide', 'erreur'], true)) { ?>
              <div class="carte-enigme-cta">
                <?php render_cta_enigme($cta, $enigme_id); ?>
              </div>
            <?php } ?>
          </div>

          <?php if ($mapping_visuel['image_reelle']) : ?>
            <h3><?= esc_html($titre); ?></h3>
          <?php endif; ?>

          <?php
          if (!empty($mapping_visuel['disponible_le'])) : ?>
            <div class="infos-dispo">
              <small class="infos-secondaires">Disponible le <?= esc_html($mapping_visuel['disponible_le']); ?></small>
            </div>
          <?php endif; ?>
        </div>
          <?php if ($classe_completion === 'carte-incomplete') : ?>
            <span
              class="warning-icon"
              aria-label="<?= esc_attr__('Énigme incomplète', 'chassesautresor-com'); ?>"
              title="<?= esc_attr__('Énigme incomplète', 'chassesautresor-com'); ?>"
            >
              <i class="fa-solid fa-exclamation" aria-hidden="true"></i>
            </span>
          <?php endif; ?>
      </article>
    <?php endforeach; ?>

    <?php
    // ➕ CTA pour ajouter une énigme si besoin
    if (utilisateur_peut_ajouter_enigme($chasse_id, $utilisateur_id) && !$has_incomplete) {
      verifier_ou_mettre_a_jour_cache_complet($chasse_id);
      $complete = (bool) get_field('chasse_cache_complet', $chasse_id);

      $highlight_pulse = false;
      if (!$has_enigmes) {
        $wp_status         = get_post_status($chasse_id);
        $statut_metier     = $infos_chasse['statut'];
        $statut_validation = $infos_chasse['statut_validation'];

        if (
          $wp_status === 'pending' &&
          $statut_metier === 'revision' &&
          in_array($statut_validation, ['creation', 'correction'], true)
        ) {
          $highlight_pulse = true;
        }
      }

      get_template_part(
          'template-parts/enigme/chasse-partial-ajout-enigme',
          null,
          [
              'has_enigmes'     => $has_enigmes,
              'chasse_id'       => $chasse_id,
              'disabled'        => !$complete,
              'highlight_pulse' => $highlight_pulse,
              'use_button'      => false,
              'show_info_icon'  => ($est_orga_associe && $needs_validatable_message),
          ]
      );
    }
    ?>
  </div>
</div>