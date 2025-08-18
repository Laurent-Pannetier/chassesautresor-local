<?php
/**
 * Displays riddle statistics table for a hunt.
 *
 * Variables:
 * - $enigmes (array)
 * - $total (int) Total participants in the hunt.
 */

defined('ABSPATH') || exit();

$args          = $args ?? [];
$enigmes       = $args['enigmes'] ?? $enigmes ?? [];
$total         = $args['total'] ?? $total ?? 0;
$title         = $args['title'] ?? '';
$cols_etiquette = $args['cols_etiquette'] ?? [];

if (empty($enigmes)) {
    return;
}

if ($title !== '') {
    echo '<h3>' . esc_html($title) . '</h3>';
}
?>
<table class="stats-table compact">
  <thead>
    <tr>
      <th scope="col" rowspan="2">Titre</th>
      <th scope="col" colspan="2">Participants</th>
      <th scope="col" rowspan="2"<?= in_array(4, $cols_etiquette, true) ? ' data-format="etiquette" data-col="4"' : ''; ?>>Tentatives</th>
      <th scope="col" rowspan="2"<?= in_array(5, $cols_etiquette, true) ? ' data-format="etiquette" data-col="5"' : ''; ?>>Points</th>
      <th scope="col" colspan="2">Bonnes réponses</th>
    </tr>
    <tr>
      <th scope="col"<?= in_array(2, $cols_etiquette, true) ? ' data-format="etiquette" data-col="2"' : ''; ?>>Nombre</th>
      <th scope="col"<?= in_array(3, $cols_etiquette, true) ? ' data-format="etiquette" data-col="3"' : ''; ?>>Taux</th>
      <th scope="col"<?= in_array(6, $cols_etiquette, true) ? ' data-format="etiquette" data-col="6"' : ''; ?>>Nombre</th>
      <th scope="col"<?= in_array(7, $cols_etiquette, true) ? ' data-format="etiquette" data-col="7"' : ''; ?>>Taux</th>
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
      <td><?= $e['resolutions'] && $e['engagements'] > 0 ? esc_html(number_format((100 * $e['resolutions']) / $e['engagements'], 1, ',', ' ') . '%') : ''; ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

