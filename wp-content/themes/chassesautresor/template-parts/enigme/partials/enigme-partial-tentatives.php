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
$par_page = $args['par_page'] ?? $par_page ?? 5;
$total = $args['total'] ?? $total ?? 0;
$pages = $args['pages'] ?? $pages ?? (int) ceil($total / $par_page);
?>
<?php if (empty($tentatives)) : ?>
<p><?= esc_html__('Aucune tentative de soumission.', 'chassesautresor-com'); ?></p>
<?php else : ?>
<table class="table-tentatives">
  <thead>
      <tr>
        <th><?= esc_html__('Date', 'chassesautresor-com'); ?></th>
        <th><?= esc_html__('Utilisateur', 'chassesautresor-com'); ?></th>
        <th><?= esc_html__('Réponse', 'chassesautresor-com'); ?></th>
        <th><?= esc_html__('Actions', 'chassesautresor-com'); ?></th>
      </tr>
  </thead>
  <tbody>
  <?php foreach ($tentatives as $tent) :
    $user  = get_userdata($tent->user_id);
    $login = ($user && isset($user->user_login)) ? $user->user_login : esc_html__('Inconnu', 'chassesautresor-com');
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
            <button type="submit" name="action_traitement" value="valider" class="bouton-cta">
              <?= esc_html__('Valider', 'chassesautresor-com'); ?>
            </button>
            <button type="submit" name="action_traitement" value="invalider" class="bouton-secondaire">
              <?= esc_html__('Invalider', 'chassesautresor-com'); ?>
            </button>
          </form>
        <?php else: ?>
          <?= esc_html($tent->resultat); ?>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php echo cta_render_pager($page, $pages, 'enigme-tentatives-pager'); ?>
<?php endif; ?>
