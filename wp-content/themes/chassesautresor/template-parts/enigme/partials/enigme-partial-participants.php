<?php
/**
 * Affiche le tableau des participants engagés sur une énigme.
 * Variables requises :
 * - $participants (array)
 * - $page (int)
 * - $par_page (int)
 * - $total (int)
 * - $pages (int)
 * - $mode_validation (string)
 * - $orderby (string)
 * - $order (string)
 */
defined('ABSPATH') || exit;

$args = $args ?? [];
$participants = $args['participants'] ?? $participants ?? [];
$page = $args['page'] ?? $page ?? 1;
$par_page = $args['par_page'] ?? $par_page ?? 25;
$total = $args['total'] ?? $total ?? 0;
$pages = $args['pages'] ?? $pages ?? (int) ceil($total / $par_page);
$mode_validation = $args['mode_validation'] ?? $mode_validation ?? 'aucune';
$orderby = $args['orderby'] ?? $orderby ?? 'date';
$order = $args['order'] ?? $order ?? 'ASC';

$icon_date = 'fa-sort';
if ($orderby === 'date') {
    $icon_date = strtoupper($order) === 'ASC' ? 'fa-sort-up' : 'fa-sort-down';
}
$icon_tentatives = 'fa-sort';
if ($orderby === 'tentatives') {
    $icon_tentatives = strtoupper($order) === 'ASC' ? 'fa-sort-up' : 'fa-sort-down';
}
?>
<?php if (empty($participants)) : ?>
<p>Aucun participant engagé.</p>
<?php else : ?>
<table class="stats-table compact">
  <thead>
    <tr>
      <th scope="col">Rang</th>
      <th scope="col">Nom</th>
      <th scope="col"><button class="sort" data-orderby="date" aria-label="Trier par date">Date <i class="fa-solid <?= esc_attr($icon_date); ?>"></i></button></th>
      <?php if ($mode_validation !== 'aucune') : ?>
      <th scope="col"><button class="sort" data-orderby="tentatives" aria-label="Trier par nombre d'essais">Nb essais <i class="fa-solid <?= esc_attr($icon_tentatives); ?>"></i></button></th>
      <?php endif; ?>
      <th scope="col">Trouvé</th>
    </tr>
  </thead>
  <tbody>
    <?php $rang = ($page - 1) * $par_page + 1; foreach ($participants as $p) : ?>
    <tr>
      <td><?= esc_html($rang++); ?></td>
      <td><?= esc_html($p['username']); ?></td>
      <td><?= esc_html(mysql2date('d/m/Y H:i', $p['date_engagement'])); ?></td>
      <?php if ($mode_validation !== 'aucune') : ?>
      <td><?= esc_html($p['nb_tentatives']); ?></td>
      <?php endif; ?>
      <td><?= $p['trouve'] && !empty($p['date_resolution']) ? esc_html(mysql2date('d/m/Y H:i', $p['date_resolution'])) : ''; ?></td>
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
