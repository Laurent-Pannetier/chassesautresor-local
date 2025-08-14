<?php
/**
 * Displays hunt participants table.
 *
 * Variables:
 * - $participants (array)
 * - $page (int)
 * - $par_page (int)
 * - $total (int)
 * - $pages (int)
 * - $total_enigmes (int)
 */

defined('ABSPATH') || exit;

$args = $args ?? [];
$participants  = $args['participants'] ?? $participants ?? [];
$page          = $args['page'] ?? $page ?? 1;
$par_page      = $args['par_page'] ?? $par_page ?? 25;
$total         = $args['total'] ?? $total ?? 0;
$pages         = $args['pages'] ?? $pages ?? (int) ceil($total / $par_page);
$total_enigmes = $args['total_enigmes'] ?? $total_enigmes ?? 0;
?>
<h3><?= esc_html__('Joueurs', 'chassesautresor-com'); ?></h3>
<?php if (empty($participants)) : ?>
<p><?= esc_html__('Pas encore de joueur inscrit.', 'chassesautresor-com'); ?></p>
<?php else : ?>
<table class="stats-table compact">
  <thead>
    <tr>
      <th scope="col"><?= esc_html__('Joueur', 'chassesautresor-com'); ?></th>
      <th scope="col"><?= esc_html__('Inscription', 'chassesautresor-com'); ?></th>
      <th scope="col"><?= esc_html__('Énigmes', 'chassesautresor-com'); ?></th>
      <th scope="col"><?= esc_html__('Taux de participation', 'chassesautresor-com'); ?></th>
      <th scope="col"><?= esc_html__('Taux de résolution', 'chassesautresor-com'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($participants as $p) :
        $links = [];
        foreach ($p['enigmes'] as $e) {
            $links[] = '<a href="' . esc_url($e['url']) . '">' . esc_html($e['title']) . '</a>';
        }
        $taux_participation = $total_enigmes > 0 ? (100 * $p['nb_engagees'] / $total_enigmes) : 0;
        $taux_resolution    = $total_enigmes > 0 ? (100 * $p['nb_resolues'] / $total_enigmes) : 0;
    ?>
    <tr>
      <td><?= esc_html($p['username']); ?></td>
      <td><?= $p['date_inscription'] ? esc_html(mysql2date('d/m/Y H:i', $p['date_inscription'])) : ''; ?></td>
      <td><?= implode(', ', $links); ?></td>
      <td><?= esc_html(number_format_i18n($taux_participation, 0)); ?>%</td>
      <td><?= esc_html(number_format_i18n($taux_resolution, 0)); ?>%</td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<div class="pager">
  <?php if ($page > 1) : ?>
    <button class="pager-first" aria-label="Première page"><i class="fa-solid fa-angles-left"></i></button>
    <button class="pager-prev" aria-label="Page précédente"><i class="fa-solid fa-angle-left"></i></button>
  <?php endif; ?>
  <span class="pager-info"><?= esc_html($page); ?> / <?= esc_html($pages); ?></span>
  <?php if ($page < $pages) : ?>
    <button class="pager-next" aria-label="Page suivante"><i class="fa-solid fa-angle-right"></i></button>
    <button class="pager-last" aria-label="Dernière page"><i class="fa-solid fa-angles-right"></i></button>
  <?php endif; ?>
</div>
<?php endif; ?>
