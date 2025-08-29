<?php
defined('ABSPATH') || exit;
/**
 * Template part : Carte d’ajout d’énigme
 *
 * Contexte attendu :
 * - $args['has_enigmes'] (bool) : indique s’il y a déjà des énigmes
 */

$has_enigmes     = $args['has_enigmes'] ?? false;
$chasse_id       = $args['chasse_id'] ?? null;
$disabled        = $args['disabled'] ?? true;
$highlight_pulse = $args['highlight_pulse'] ?? false;
$use_button      = $args['use_button'] ?? false;
if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') return;

$ajout_url = esc_url(add_query_arg('chasse_id', $chasse_id, home_url('/creer-enigme/')));

?>

<?php if ($use_button) : ?>
  <a
    href="<?php echo $ajout_url; ?>"
    id="carte-ajout-enigme"
    data-post-id="0"
  >
    <i class="fa-solid fa-circle-plus fa-lg" aria-hidden="true"></i>
    <span><?php echo esc_html__('Ajouter une énigme', 'chassesautresor-com'); ?></span>
  </a>
<?php else : ?>
    <a
        href="<?php echo $ajout_url; ?>"
        id="carte-ajout-enigme"
        class="carte carte-enigme carte-ajout-enigme <?php echo $has_enigmes ? 'etat-suivante' : 'etat-vide'; ?> <?php echo $disabled ? 'disabled' : ''; ?><?php echo $highlight_pulse ? ' pulsation' : ''; ?>"
        data-post-id="0">
        <div class="carte-core">
            <div class="carte-enigme-image">
                <div class="carte-enigme-cta">
                    <span class="bouton-cta bouton-cta--color">
                        <?php echo $has_enigmes
                            ? esc_html__('Ajouter une énigme', 'chassesautresor-com')
                            : esc_html__('Créer la première énigme', 'chassesautresor-com'); ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="overlay-message">
            <i class="fa-solid fa-circle-info"></i>
            <p><?php echo esc_html__('Complétez d’abord : titre, image, description', 'chassesautresor-com'); ?></p>
        </div>
    </a>
<?php endif; ?>

