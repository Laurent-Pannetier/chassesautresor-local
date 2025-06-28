<?php
defined('ABSPATH') || exit;

$organisateur_id = $args['organisateur_id'] ?? null;
$has_chasses     = $args['has_chasses'] ?? false;
$highlight_pulse = $args['highlight_pulse'] ?? false;
$use_button      = $args['use_button'] ?? false;


if (!$organisateur_id || get_post_type($organisateur_id) !== 'organisateur') {
  return;
}
?>

<?php if ($use_button) : ?>
  <a
    href="<?= esc_url(site_url('/creer-chasse/')); ?>"
    id="carte-ajout-chasse"
    data-post-id="0"
  >
    <i class="fa-solid fa-circle-plus fa-lg"></i>
    <span class="screen-reader-text">Ajouter une chasse</span>
  </a>
<?php else : ?>
  <a
    href="<?= esc_url(site_url('/creer-chasse/')); ?>"
    id="carte-ajout-chasse"
    class="carte-chasse carte-ajout-chasse disabled<?= $highlight_pulse ? ' pulsation' : ''; ?>"
    data-post-id="0">

    <div class="carte-chasse-contenu">
      <div class="icone-ajout">
        <i class="fa-solid fa-circle-plus fa-3x"></i>
      </div>
      <h2><?= $has_chasses ? 'Ajouter une nouvelle chasse' : 'Créer ma première chasse'; ?></h2>
    </div>

    <div class="overlay-message">
      <i class="fa-solid fa-circle-info"></i>
      <p>Complétez d’abord : titre, logo, description</p>
    </div>
  </a>
<?php endif; ?>
