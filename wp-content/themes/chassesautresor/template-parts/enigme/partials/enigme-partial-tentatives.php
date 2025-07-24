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
  ?>
    <tr>
      <td><?= esc_html($date); ?></td>
      <td><?= esc_html($login); ?></td>
      <td><?= esc_html($tent->reponse_saisie); ?></td>
      <td>
        <?php if ($tent->resultat === 'attente'): ?>
          <form method="post" style="display:inline;">
            <?php wp_nonce_field('traiter_tentative_' . $tent->tentative_uid); ?>
            <input type="hidden" name="uid" value="<?= esc_attr($tent->tentative_uid); ?>">
            <button type="submit" name="action_traitement" value="valider">Valider</button>
            <button type="submit" name="action_traitement" value="invalider">Invalider</button>
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
    <button class="pager-prev">&laquo; Préc.</button>
  <?php endif; ?>
  <?php if ((($page - 1) * $par_page) + $par_page < $total) : ?>
    <button class="pager-next" style="margin-left:10px;">Suiv. &raquo;</button>
  <?php endif; ?>
</div>
<?php endif; ?>
