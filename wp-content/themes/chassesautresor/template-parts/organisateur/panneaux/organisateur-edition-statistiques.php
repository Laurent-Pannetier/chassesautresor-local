<?php
/**
 * Organizer statistics panel.
 */

defined('ABSPATH') || exit;

$organisateur_id = $args['organisateur_id'] ?? 0;
$joueurs = organisateur_compter_joueurs_uniques($organisateur_id);
$points  = organisateur_compter_points_collectes($organisateur_id);
?>
<div class="edition-panel-body">
  <div class="edition-stats-cards">
    <div class="edition-stats-card">
      <i class="fa-solid fa-users" aria-hidden="true"></i>
      <div class="edition-stats-card-content">
        <span class="edition-stats-card-title">Joueurs</span>
        <span class="edition-stats-card-number"><?php echo esc_html($joueurs); ?></span>
      </div>
    </div>
    <div class="edition-stats-card">
      <i class="fa-solid fa-coins" aria-hidden="true"></i>
      <div class="edition-stats-card-content">
        <span class="edition-stats-card-title">Points collectés</span>
        <span class="edition-stats-card-number"><?php echo esc_html($points); ?></span>
      </div>
    </div>
  </div>
  <p class="edition-placeholder">Aucune statistique détaillée pour le moment.</p>
</div>
