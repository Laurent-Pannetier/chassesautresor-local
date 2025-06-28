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
    class="carte carte-ligne carte-chasse carte-ajout-chasse disabled<?= $highlight_pulse ? ' pulsation' : ''; ?>"
    data-post-id="0">

    <div class="carte-ligne__image">
      <div class="icone-ajout">
        <i class="fa-solid fa-circle-plus fa-3x"></i>
      </div>
    </div>

    <div class="carte-ligne__contenu">
      <h3 class="carte-ligne__titre"><?= $has_chasses ? 'Ajouter une nouvelle chasse' : 'Créer ma première chasse'; ?></h3>
      <div class="overlay-message">
        <i class="fa-solid fa-circle-info"></i>
        <p>Complétez d’abord : titre, logo, description</p>
      </div>
    </div>
  </a>
<?php endif; ?>
