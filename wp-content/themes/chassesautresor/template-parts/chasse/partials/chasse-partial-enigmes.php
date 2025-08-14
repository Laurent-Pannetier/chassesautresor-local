<?php
/**
 * Displays riddle statistics table for a hunt.
 *
 * Variables:
 * - $enigmes (array)
 * - $total (int) Total engagements in the hunt.
 */

defined('ABSPATH') || exit;

$args    = $args ?? [];
$enigmes = $args['enigmes'] ?? $enigmes ?? [];
$total   = $args['total'] ?? $total ?? 0;
?>
<h3>Énigmes</h3>
<?php if (empty($enigmes)) : ?>
<p>Aucune énigme.</p>
<?php else : ?>
<table class="stats-table compact stats-table-grouped">
  <thead>
    <tr>
      <th scope="col" rowspan="2">Titre</th>
      <th scope="col" colspan="2">Participants</th>
      <th scope="col" rowspan="2">Tentatives</th>
      <th scope="col" rowspan="2">Points</th>
      <th scope="col" colspan="2">Bonnes réponses</th>
    </tr>
    <tr>
      <th scope="col">Nombre</th>
      <th scope="col">Taux</th>
      <th scope="col">Nombre</th>
      <th scope="col">Taux</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($enigmes as $e) : ?>
    <tr>
      <td><a href="<?= esc_url(get_permalink($e['id'])); ?>"><?= esc_html($e['titre']); ?></a></td>
      <td><?= esc_html($e['engagements']); ?></td>
      <td><?= $total > 0 ? esc_html(number_format((100 * $e['engagements']) / $total, 1, ',', ' ') . '%') : '0%'; ?></td>
      <td><?= $e['tentatives'] ? esc_html($e['tentatives']) : ''; ?></td>
      <td><?= $e['points'] ? esc_html($e['points']) : ''; ?></td>
      <td><?= $e['resolutions'] ? esc_html($e['resolutions']) : ''; ?></td>
      <td><?= $e['resolutions'] && $e['engagements'] > 0
        ? esc_html(number_format((100 * $e['resolutions']) / $e['engagements'], 1, ',', ' ') . '%')
        : ''; ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
