<?php
defined('ABSPATH') || exit;


$organisateur_id = $args['organisateur_id'] ?? null;
if (!$organisateur_id || get_post_type($organisateur_id) !== 'organisateur') {
  return;
}

$show_header  = $args['show_header'] ?? true;
$grid_class   = $args['grid_class'] ?? 'liste-pleine-largeur';
$before_items = $args['before_items'] ?? '';
$after_items  = $args['after_items'] ?? '';

$query      = get_chasses_de_organisateur($organisateur_id);
$chasse_ids = is_a($query, 'WP_Query') ? $query->posts : (array) $query;

// 🔒 Filtrer les chasses visibles selon leur statut et l'utilisateur courant
$user_id    = get_current_user_id();
$chasse_ids = array_values(array_filter($chasse_ids, function ($chasse_id) use ($user_id) {
  return chasse_est_visible_pour_utilisateur((int) $chasse_id, $user_id);
}));

?>

<?php if ($show_header) : ?>
<h2>Ses chasses</h2>
<div class="separateur-2"></div>
<?php endif; ?>
<div class="<?php echo esc_attr($grid_class); ?>">
<?php echo $before_items; ?>
  <?php foreach ($chasse_ids as $chasse_id) : ?>
    <?php
    $chasse_id      = (int) $chasse_id;
    $est_orga       = est_organisateur();
    $wp_status      = get_post_status($chasse_id);
    $voir_bordure   = $est_orga &&
      utilisateur_est_organisateur_associe_a_chasse(get_current_user_id(), $chasse_id) &&
      $wp_status !== 'publish';
    $classe_completion = '';
    if ($voir_bordure) {
      verifier_ou_mettre_a_jour_cache_complet($chasse_id);
      $complet          = (bool) get_field('chasse_cache_complet', $chasse_id);
      $classe_completion = $complet ? 'carte-complete' : 'carte-incomplete';
    }
    get_template_part('template-parts/chasse/chasse-card-wide', null, [
      'chasse_id'        => $chasse_id,
      'completion_class' => $classe_completion,
      'word_limit'       => 150,
    ]);
    ?>
  <?php endforeach; ?>
<?php echo $after_items; ?>
</div>
