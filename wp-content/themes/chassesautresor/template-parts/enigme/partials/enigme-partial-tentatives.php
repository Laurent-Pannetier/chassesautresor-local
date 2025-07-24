<?php
/**
 * Affiche le tableau des tentatives de soumission pour une énigme.
 * Variables requises :
 * - $tentatives (array)
 * - $page (int)
 * - $par_page (int)
 * - $total (int)
 */
defined('ABSPATH') || exit;

$args = $args ?? [];
$tentatives = $args['tentatives'] ?? $tentatives ?? [];
$page = $args['page'] ?? $page ?? 1;
$par_page = $args['par_page'] ?? $par_page ?? 10;
$total = $args['total'] ?? $total ?? 0;
$pages = $args['pages'] ?? $pages ?? (int) ceil($total / $par_page);
?>
<?php if (empty($tentatives)) : ?>
<p>Aucune tentative de soumission.</p>
<?php else : ?>
<table class="table-tentatives">
  <thead>
    <tr>
      <th>Date</th>
      <th>Utilisateur</th>
      <th>Réponse</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($tentatives as $tent) :
    $user  = get_userdata($tent->user_id);
    $login = ($user && isset($user->user_login)) ? $user->user_login : 'Inconnu';
    $date  = mysql2date('d/m/y H:i', $tent->date_tentative);
    $pending = ($tent->resultat === 'attente' && (int) $tent->traitee === 0) ? ' tentative-pending' : '';
  ?>
    <tr class="<?= $pending ?>">
      <td><?= esc_html($date); ?></td>
      <td><?= esc_html($login); ?></td>
      <td><?= esc_html($tent->reponse_saisie); ?></td>
      <td>
        <?php if ($tent->resultat === 'attente'): ?>
          <form method="post" style="display:inline;">
            <?php wp_nonce_field('traiter_tentative_' . $tent->tentative_uid); ?>
            <input type="hidden" name="uid" value="<?= esc_attr($tent->tentative_uid); ?>">
            <button type="submit" name="action_traitement" value="valider" class="btn-valider">Valider</button>
            <button type="submit" name="action_traitement" value="invalider" class="btn-refuser">Invalider</button>
          </form>
        <?php else: ?>
          <?= esc_html($tent->resultat); ?>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<div class="pager" style="margin-top:10px;">
  <?php if ($page > 1) : ?>
    <button class="pager-first" aria-label="Première page"><i class="fa-solid fa-angles-left"></i></button>
    <button class="pager-prev" aria-label="Page précédente" style="margin-left:5px;"><i class="fa-solid fa-angle-left"></i></button>
  <?php endif; ?>
  <span class="pager-info" style="margin:0 8px;"><?= esc_html($page); ?> / <?= esc_html($pages); ?></span>
  <?php if ($page < $pages) : ?>
    <button class="pager-next" aria-label="Page suivante" style="margin-left:10px;"><i class="fa-solid fa-angle-right"></i></button>
    <button class="pager-last" aria-label="Dernière page" style="margin-left:5px;"><i class="fa-solid fa-angles-right"></i></button>
  <?php endif; ?>
</div>
<?php endif; ?>
