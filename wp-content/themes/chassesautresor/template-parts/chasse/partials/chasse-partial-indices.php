<?php
/**
 * Displays indices add card for a hunt or riddle.
 *
 * Variables:
 * - $objet_id   (int)
 * - $objet_type ('chasse'|'enigme')
 */

defined('ABSPATH') || exit;

$args       = $args ?? [];
$objet_id   = $args['objet_id'] ?? $chasse_id ?? 0;
$objet_type = $args['objet_type'] ?? 'chasse';
$default_enigme = $args['default_enigme'] ?? null;

$objet_titre = get_the_title($objet_id);
$indice_rang = prochain_rang_indice($objet_id, $objet_type);

$peut_ajouter = indice_action_autorisee('create', $objet_type, $objet_id);
?>
<div class="dashboard-card champ-<?= esc_attr($objet_type); ?> champ-indices<?= $peut_ajouter ? '' : ' disabled'; ?>">
  <i class="fa-solid fa-kit-medical icone-defaut" aria-hidden="true"></i>
  <h3><?= esc_html__('Ajouter un indice pour…', 'chassesautresor-com'); ?></h3>
  <?php if ($peut_ajouter) : ?>
    <div class="stat-value">
      <a
        href="#"
        class="bouton-cta cta-creer-indice cta-indice-chasse"
        data-objet-type="<?= esc_attr($objet_type); ?>"
        data-objet-id="<?= esc_attr($objet_id); ?>"
        data-objet-titre="<?= esc_attr($objet_titre); ?>"
        data-indice-rang="<?= esc_attr($indice_rang); ?>"
      >
        <?= esc_html__('La chasse entière', 'chassesautresor-com'); ?>
      </a>
      <a
        href="#"
        class="bouton-cta cta-indice-enigme"
        data-objet-type="enigme"
        <?php if ($default_enigme) : ?>
          data-default-enigme="<?= esc_attr($default_enigme); ?>"
        <?php endif; ?>
      >
        <?= esc_html__('Une énigme de la chasse', 'chassesautresor-com'); ?>
      </a>
    </div>
  <?php else : ?>
    <span class="stat-value">
      <?= esc_html__('Ajouter', 'chassesautresor-com'); ?>
    </span>
  <?php endif; ?>
</div>
