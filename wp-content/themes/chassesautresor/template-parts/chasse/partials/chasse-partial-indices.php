<?php
/**
 * Displays indices add card for a hunt or riddle.
 *
 * Variables:
 * - $objet_id   (int)
 * - $objet_type ('chasse'|'enigme')
 */

defined('ABSPATH') || exit;

$args       = $args ?? [];
$objet_id   = $args['objet_id'] ?? $chasse_id ?? 0;
$objet_type = $args['objet_type'] ?? 'chasse';

$peut_ajouter = indice_action_autorisee('create', $objet_type, $objet_id);
$ajout_url    = '';

if ($peut_ajouter) {
    $query_arg = $objet_type === 'enigme' ? 'enigme_id' : 'chasse_id';
    $ajout_url = wp_nonce_url(
        add_query_arg($query_arg, $objet_id, home_url('/creer-indice/')),
        'creer_indice',
        'nonce'
    );
}
?>
<div class="dashboard-card champ-<?= esc_attr($objet_type); ?> champ-indices<?= $peut_ajouter ? '' : ' disabled'; ?>">
  <i class="fa-regular fa-circle-question icone-defaut" aria-hidden="true"></i>
  <h3><?= esc_html__('Indices', 'chassesautresor-com'); ?></h3>
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
