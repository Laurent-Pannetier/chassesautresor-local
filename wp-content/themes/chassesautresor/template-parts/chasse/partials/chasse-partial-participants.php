<?php
/**
 * Displays hunt engagements (participants) table.
 *
 * Variables:
 * - $participants (array)
 * - $page (int)
 * - $par_page (int)
 * - $total (int)
 * - $pages (int)
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
<p class="participants-count"><?= esc_html($total); ?> engagements</p>
<?php if (empty($participants)) : ?>
<p>Aucun engagement.</p>
<?php else : ?>
<table class="stats-table compact">
  <thead>
    <tr>
      <th scope="col">Rang</th>
      <th scope="col">Joueur</th>
      <th scope="col"><button class="sort" data-orderby="date" aria-label="Trier par date">Chasse <i class="fa-solid <?= esc_attr($icon_date); ?>"></i></button></th>
      <th scope="col">Énigme</th>
      <th scope="col"><button class="sort" data-orderby="tentatives" aria-label="Trier par nombre d'essais">Nb essais <i class="fa-solid <?= esc_attr($icon_tentatives); ?>"></i></button></th>
    </tr>
  </thead>
  <tbody>
    <?php $rang = ($page - 1) * $par_page + 1; foreach ($participants as $p) : ?>
    <tr>
      <td><?= esc_html($rang++); ?></td>
      <td><?= esc_html($p['username']); ?></td>
      <td><?= $p['date_chasse'] ? esc_html(mysql2date('d/m/Y H:i', $p['date_chasse'])) : ''; ?></td>
      <td><?= $p['date_enigme'] ? esc_html(mysql2date('d/m/Y H:i', $p['date_enigme'])) : ''; ?></td>
      <td><?= esc_html($p['nb_tentatives']); ?></td>
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
