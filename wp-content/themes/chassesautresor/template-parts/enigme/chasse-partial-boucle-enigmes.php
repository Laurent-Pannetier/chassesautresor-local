<?php
defined('ABSPATH') || exit;

// error_log("🔍 [BLOC ENIGMES] Début exécution partial chasse-partial-boucle-enigmes.php");

$chasse_id = $args['chasse_id'] ?? null;
if (!$chasse_id) {
  // error_log("❌ [BLOC ENIGMES] Aucun ID de chasse fourni.");
  return;
}

$post_type = get_post_type($chasse_id);
// error_log("ℹ️ [BLOC ENIGMES] chasse_id = $chasse_id | post_type = $post_type");

if ($post_type !== 'chasse') {
  // error_log("❌ [BLOC ENIGMES] Le post n'est pas de type 'chasse'.");
  return;
}

$utilisateur_id = get_current_user_id();
// error_log("👤 [BLOC ENIGMES] Utilisateur courant ID = $utilisateur_id");

if (!chasse_est_visible_pour_utilisateur($chasse_id, $utilisateur_id)) {
  // error_log("❌ [BLOC ENIGMES] chasse_est_visible_pour_utilisateur = false → sortie");
  return;
}
// error_log("✅ [BLOC ENIGMES] chasse_est_visible_pour_utilisateur = true");

// 🔒 Vérifie autorisation à voir la boucle d'énigmes
$autorise_boucle = (
  user_can($utilisateur_id, 'manage_options') ||
  utilisateur_est_organisateur_associe_a_chasse($utilisateur_id, $chasse_id) ||
  utilisateur_est_engage_dans_chasse($utilisateur_id, $chasse_id)
);
// error_log("🔐 [BLOC ENIGMES] autorisé à voir boucle ? " . ($autorise_boucle ? 'OUI' : 'NON'));

if (!$autorise_boucle) {
  // error_log("❌ [BLOC ENIGMES] utilisateur non autorisé à voir la boucle → sortie");
  return;
}
// error_log("✅ [BLOC ENIGMES] utilisateur autorisé à voir la boucle");

// 🔎 Récupération des énigmes associées à la chasse
$posts = get_posts([
  'post_type'      => 'enigme',
  'posts_per_page' => -1,
  'orderby'        => 'menu_order',
  'order'          => 'ASC',
  'post_status'    => ['publish', 'pending', 'draft'],
  'meta_query'     => [[
  'key'     => 'enigme_chasse_associee',
  'value'   => '"' . $chasse_id . '"',
  'compare' => 'LIKE',
  ]]
]);

// error_log("📦 [BLOC ENIGMES] Nombre d'énigmes récupérées : " . count($posts));

// 💡 Optionnel : afficher les ID récupérés
// foreach ($posts as $post) {
//     error_log("🧩 [ENIGME] ID = {$post->ID} | statut = " . get_post_status($post));
// }

$posts_visibles = $posts;
$has_enigmes = !empty($posts_visibles);
// error_log("👁️ [BLOC ENIGMES] posts_visibles = " . ($has_enigmes ? 'OUI' : 'NON'));

// 🟠 Détection des énigmes incomplètes
$has_incomplete = false;
foreach ($posts as $p) {
  verifier_ou_mettre_a_jour_cache_complet($p->ID);
  $complet = (bool) get_field('enigme_cache_complet', $p->ID);
  // error_log("📋 [ENIGME #{$p->ID}] complet ? " . ($complet ? 'OUI' : 'NON'));
  if (!$complet) {
  $has_incomplete = true;
  break;
  }
}
// error_log("✅ [BLOC ENIGMES] has_incomplete = " . ($has_incomplete ? 'OUI' : 'NON'));

?>

<div class="bloc-enigmes-chasse">
  <div class="grille-3">
  <?php foreach ($posts_visibles as $post): ?>
  <?php
  $enigme_id = $post->ID;
  $titre = get_the_title($enigme_id);
  $etat_systeme = enigme_get_etat_systeme($enigme_id);
  $statut_utilisateur = enigme_get_statut_utilisateur($enigme_id, $utilisateur_id);
  $cta = get_cta_enigme($enigme_id);

  $est_orga = est_organisateur();
  $statut_chasse = get_post_status($chasse_id);
  $statut_enigme = get_post_status($enigme_id);
  $voir_bordure = $est_orga &&
    utilisateur_est_organisateur_associe_a_chasse($utilisateur_id, $chasse_id) &&
    $statut_chasse !== 'publish' &&
    $statut_enigme !== 'publish';
  $classe_completion = '';
  if ($voir_bordure) {
    verifier_ou_mettre_a_jour_cache_complet($enigme_id);
    $complet = (bool) get_field('enigme_cache_complet', $enigme_id);
    $classe_completion = $complet ? 'carte-complete' : 'carte-incomplete';
  }
  ?>
  <article class="carte carte-enigme <?= esc_attr($classe_completion); ?>">
    <div class="carte-core">
    <div class="carte-enigme-image">
      <?php afficher_picture_vignette_enigme($enigme_id, 'Vignette de l’énigme', ['medium']); ?>
      <div class="carte-enigme-cta">
      <?php render_cta_enigme($cta, $enigme_id); ?>
      </div>
    </div>
    <h3><?= esc_html($titre); ?></h3>
    </div>
  </article>

  <?php endforeach; ?>

  <?php
  if (utilisateur_peut_ajouter_enigme($chasse_id, $utilisateur_id) && !$has_incomplete && !$has_enigmes) {
  verifier_ou_mettre_a_jour_cache_complet($chasse_id);
  $complete = (bool) get_field('chasse_cache_complet', $chasse_id);

  $highlight_pulse = false;
  if (!$has_enigmes) {
    $wp_status         = get_post_status($chasse_id);
    $statut_metier     = get_field('chasse_cache_statut', $chasse_id);
    $statut_validation = get_field('chasse_cache_statut_validation', $chasse_id);

    if (
    $wp_status === 'pending' &&
    $statut_metier === 'revision' &&
    in_array($statut_validation, ['creation', 'correction'], true)
    ) {
    $highlight_pulse = true;
    }
  }

  get_template_part('template-parts/enigme/chasse-partial-ajout-enigme', null, [
    'has_enigmes'     => $has_enigmes,
    'chasse_id'       => $chasse_id,
    'disabled'        => !$complete,
    'highlight_pulse' => $highlight_pulse,
    'use_button'      => false,
  ]);
  }
  ?>
</div>
  </div>
