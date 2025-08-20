<?php
/**
 * Liste des gagnants d'une énigme.
 * Variables attendues :
 * - $gagnants (array)
 * - $page (int)
 * - $pages (int)
 * - $user_id (int)
 * - $user_rank (int|null)
 */

defined('ABSPATH') || exit;

$args      = $args ?? [];
$gagnants  = $args['gagnants'] ?? $gagnants ?? [];
$page      = $args['page'] ?? $page ?? 1;
$pages     = $args['pages'] ?? $pages ?? 1;
$user_id   = $args['user_id'] ?? $user_id ?? 0;
$user_rank = $args['user_rank'] ?? $user_rank ?? null;
?>
<h3>
  <?php esc_html_e('Gagnants', 'chassesautresor-com'); ?>
  <?php if ($user_rank) : ?>
    <i class="fa-solid fa-trophy" aria-hidden="true"></i>
    <span class="etiquette">#<?php echo esc_html($user_rank); ?></span>
    <span class="etiquette etiquette-success"><?php esc_html_e('Résolu', 'chassesautresor-com'); ?></span>
  <?php endif; ?>
</h3>
<?php if (!empty($gagnants)) : ?>
<table class="stats-table compact">
  <thead>
    <tr>
      <th scope="col"><?php esc_html_e('Rang', 'chassesautresor-com'); ?></th>
      <th scope="col"><?php esc_html_e('Nom', 'chassesautresor-com'); ?></th>
      <th scope="col"><?php esc_html_e('Date', 'chassesautresor-com'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($gagnants as $index => $g) :
        $rank = ($page - 1) * 10 + $index + 1;
        $highlight = ((int) $g['user_id'] === (int) $user_id) ? ' class="current-user"' : '';
    ?>
    <tr<?php echo $highlight; ?>>
      <td><?php echo esc_html($rank); ?></td>
      <td><?php echo esc_html($g['username']); ?></td>
      <td><?php echo esc_html(mysql2date('d/m/Y H:i', $g['date'])); ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php echo cta_render_pager($page, $pages, 'enigme-gagnants-pager'); ?>
<?php else : ?>
<p><?php esc_html_e('Aucun gagnant pour le moment.', 'chassesautresor-com'); ?></p>
<?php endif; ?>
