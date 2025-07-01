<?php
defined('ABSPATH') || exit;

$description = $args['description'] ?? '';
$titre_recompense = $args['titre_recompense'] ?? '';
$lot = $args['lot'] ?? '';
$valeur_recompense = $args['valeur_recompense'] ?? '';
$chasse_id = $args['chasse_id'] ?? 0;
$nb_max = $args['nb_max'] ?? 0;
$mode = $args['mode'] ?? 'complet'; // 'complet' ou 'compact'
?>


<section class="chasse-description-section bloc-elegant" id="chasse-description">
  <?php if (!empty($description)) : ?>
    <div class="chasse-description">
      <?= wp_kses_post($description); ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($titre_recompense) || (float) $valeur_recompense > 0 || !empty($lot)) : ?>
    <div class="chasse-lot-complet" style="margin-top: 30px;">
      <h3>🏆 Récompense de la chasse</h3>

      <?php if (!empty($titre_recompense)) : ?>
        <p><strong>Titre :</strong> <?= esc_html($titre_recompense); ?></p>
      <?php endif; ?>

      <?php if ((float) $valeur_recompense > 0) : ?>
        <p><strong>Valeur :</strong> <?= esc_html($valeur_recompense); ?> €</p>
      <?php endif; ?>

      <?php if (!empty($lot)) : ?>
        <p><strong>Description complète :</strong><br><?= wp_kses_post($lot); ?></p>
      <?php endif; ?>

    </div>
  <?php endif; ?>
</section>
