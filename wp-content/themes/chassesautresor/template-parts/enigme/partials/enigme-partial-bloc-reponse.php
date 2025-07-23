<?php
defined('ABSPATH') || exit;

$post_id = $args['post_id'] ?? null;
$user_id = $args['user_id'] ?? get_current_user_id(); // ✅ sécurisation

error_log("👤 STATUT ACTUEL : " . enigme_get_statut_utilisateur($post_id, $user_id));


if (!$post_id || !$user_id) return;

// 🛡️ Organisateur / admin : on n'affiche rien
$chasse_id = recuperer_id_chasse_associee($post_id);
if (
  current_user_can('manage_options') ||
  utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id)
) {
  echo '<p class="message-organisateur">🛠️ Cette énigme est la vôtre. Aucun formulaire n’est affiché.</p>';
  return;
}

// Récupération du mode de validation
$mode_validation = get_field('enigme_mode_validation', $post_id);
if (!in_array($mode_validation, ['automatique', 'manuelle'])) return;

$cout = (int) get_field('enigme_tentative_cout_points', $post_id);
$max  = (int) get_field('enigme_tentative_max', $post_id);

if ($mode_validation === 'manuelle') {
  if (!utilisateur_peut_repondre_manuelle($user_id, $post_id)) {
    $statut = enigme_get_statut_utilisateur($post_id, $user_id);
    $texte = $statut === 'soumis'
      ? 'Votre tentative est en cours de traitement.'
      : 'Vous avez déjà répondu ou résolu cette énigme.';
    echo '<p class="message-joueur-statut">' . esc_html($texte) . '</p>';
    return;
  }
  echo '<div class="bloc-reponse">' . do_shortcode('[formulaire_reponse_manuelle id="' . esc_attr($post_id) . '"]') . '</div>';
  return;
}

$statut_actuel = enigme_get_statut_utilisateur($post_id, $user_id);
if ($statut_actuel === 'resolue') {
  echo '<p class="message-joueur-statut">Vous avez déjà résolu cette énigme.</p>';
  return;
}

$tentatives_du_jour = compter_tentatives_du_jour($user_id, $post_id);
$boutique_url = esc_url(home_url('/boutique/'));
$disabled = '';
$label_btn = 'Valider';
$message_tentatives = '';

if ($max && $tentatives_du_jour >= $max) {
  $disabled = 'disabled';
  $message_tentatives = 'tentatives quotidiennes épuisées';

  $tz = new DateTimeZone('Europe/Paris');
  $now = new DateTime('now', $tz);
  $midnight = (clone $now)->modify('tomorrow')->setTime(0, 0);
  $diff = $midnight->getTimestamp() - $now->getTimestamp();
  $hours = floor($diff / 3600);
  $minutes = floor(($diff % 3600) / 60);
  $label_btn = sprintf('%02d:%02d', $hours, $minutes);
}

if ($cout > get_user_points($user_id)) {
  $disabled = 'disabled';
  $label_btn = '<a href="' . $boutique_url . '">points insuffisants</a>';
}

$nonce = wp_create_nonce('reponse_auto_nonce');
?>

<form class="bloc-reponse formulaire-reponse-auto">
  <label for="reponse_auto_<?= esc_attr($post_id); ?>">Votre réponse :</label>
  <?php if ($message_tentatives) : ?>
    <p class="message-limite" data-tentatives="epuisees"><?= esc_html($message_tentatives); ?></p>
  <?php else : ?>
    <input type="text" name="reponse" id="reponse_auto_<?= esc_attr($post_id); ?>" required>
  <?php endif; ?>
  <input type="hidden" name="enigme_id" value="<?= esc_attr($post_id); ?>">
  <input type="hidden" name="nonce" value="<?= esc_attr($nonce); ?>">
  <button type="submit" class="bouton-cta" <?= $disabled; ?>><?= $label_btn; ?></button>
</form>
<div class="reponse-feedback" style="display:none"></div>
<?php if ($max > 0) : ?>
  <p class="compteur-tentatives txt-small" data-max="<?= esc_attr($max); ?>" style="margin-top:4px;">
    <?= esc_html($tentatives_du_jour); ?> tentatives / <?= esc_html($max); ?> maximum aujourd'hui
  </p>
<?php endif; ?>

