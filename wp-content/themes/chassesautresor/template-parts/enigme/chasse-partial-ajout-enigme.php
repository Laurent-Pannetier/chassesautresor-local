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
$show_help_icon  = $args['show_help_icon'] ?? false;

if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') return;

$ajout_url = esc_url(add_query_arg('chasse_id', $chasse_id, home_url('/creer-enigme/')));

?>
<div class="carte-ajout-wrapper">
    <?php if ($show_help_icon) : ?>
        <?php
        get_template_part(
            'template-parts/common/help-icon',
            null,
            [
                'aria_label' => __('Validation en ligne nécessaire', 'chassesautresor-com'),
                'title'      => __('Validation en ligne nécessaire', 'chassesautresor-com'),
                'message'    => __('Votre chasse se termine automatiquement ; ajoutez une énigme à validation manuelle ou automatique.', 'chassesautresor-com'),
                'variant'    => 'info',
                'classes'    => 'carte-help-icon',
                'background' => 'light',
            ]
        );
        ?>
    <?php endif; ?>
    <button
        type="button"
        id="carte-ajout-enigme"
        class="carte carte-enigme carte-ajout-enigme <?php echo $has_enigmes ? 'etat-suivante' : 'etat-vide'; ?> <?php echo $disabled ? 'disabled' : ''; ?><?php echo $highlight_pulse ? ' pulsation' : ''; ?>"
        data-post-id="0"
        aria-label="<?php echo esc_attr__('Ajouter une énigme', 'chassesautresor-com'); ?>"
        <?php echo $disabled ? 'disabled' : ''; ?>
        onclick="if(!this.hasAttribute('disabled')){window.location.href='<?php echo $ajout_url; ?>';}"
    >
        <div class="carte-core">
            <i class="fa-solid fa-circle-plus" aria-hidden="true"></i>
            <span class="carte-ajout-libelle"><?php echo esc_html__('Ajouter une énigme', 'chassesautresor-com'); ?></span>
        </div>
        <div class="overlay-message">
            <i class="fa-solid fa-circle-info"></i>
            <p><?php echo esc_html__('Complétez d’abord : titre, image, description', 'chassesautresor-com'); ?></p>
        </div>
    </button>
</div>

