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
  <?php
  $chasses = get_chasses_de_organisateur($organisateur_id);
  if ($chasses && !empty($chasses->posts)) {
      foreach ($chasses->posts as $chasse) {
          $chasse_id         = $chasse->ID;
          $total_engagements = chasse_compter_engagements($chasse_id);
          $enigmes_stats     = [];
          foreach (recuperer_ids_enigmes_pour_chasse($chasse_id) as $enigme_id) {
              $engagements    = enigme_compter_joueurs_engages($enigme_id);
              $enigmes_stats[] = [
                  'id'          => $enigme_id,
                  'titre'       => get_the_title($enigme_id),
                  'engagements' => $engagements,
                  'tentatives'  => enigme_compter_tentatives($enigme_id, 'automatique'),
                  'points'      => enigme_compter_points_depenses($enigme_id, 'automatique'),
                  'resolutions' => enigme_compter_bonnes_solutions($enigme_id, 'automatique'),
              ];
          }
          get_template_part(
              'template-parts/chasse/partials/chasse-partial-enigmes',
              null,
              [
                  'title'   => get_the_title($chasse_id) . ' - Énigmes',
                  'enigmes' => $enigmes_stats,
                  'total'   => $total_engagements,
              ]
          );
      }
  } else {
      echo '<p class="edition-placeholder">Aucune statistique détaillée pour le moment.</p>';
  }
  ?>
</div>
