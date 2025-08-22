<?php
/**
 * Displays indices card for a hunt.
 *
 * Variables:
 * - $chasse_id (int)
 * - $indices (WP_Post[])
 */

defined('ABSPATH') || exit;

$args      = $args ?? [];
$chasse_id = $args['chasse_id'] ?? $chasse_id ?? 0;
$indices   = $args['indices'] ?? $indices ?? [];

$est_admin        = current_user_can('administrator');
$est_organisateur = utilisateur_est_organisateur_associe_a_chasse(get_current_user_id(), $chasse_id);
$est_publie       = get_post_status($chasse_id) === 'publish';
$statut_valide    = get_field('chasse_cache_statut_validation', $chasse_id) === 'valide';
$peut_ajouter     = $est_admin || ($est_organisateur && $est_publie && $statut_valide);
$ajout_url        = '';
if ($peut_ajouter) {
    $ajout_url = wp_nonce_url(
        add_query_arg('chasse_id', $chasse_id, home_url('/creer-indice/')),
        'creer_indice',
        'nonce'
    );
}
?>
<div class="dashboard-card champ-chasse champ-indices<?= $peut_ajouter ? '' : ' disabled'; ?>">
  <i class="fa-regular fa-circle-question icone-defaut" aria-hidden="true"></i>
  <h3><?= esc_html__('Indices', 'chassesautresor-com'); ?></h3>
  <?php if (!empty($indices)) : ?>
    <ul class="indices-list">
      <?php foreach ($indices as $indice) : ?>
        <li>
          <a href="<?= esc_url(get_permalink($indice)); ?>">
            <?= esc_html(get_the_title($indice)); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <?php if ($peut_ajouter) : ?>
    <a href="<?= esc_url($ajout_url); ?>" class="stat-value">
      <?= esc_html__('Ajouter', 'chassesautresor-com'); ?>
    </a>
  <?php else : ?>
    <span class="stat-value">
      <?= esc_html__('Ajouter', 'chassesautresor-com'); ?>
    </span>
  <?php endif; ?>
</div>
