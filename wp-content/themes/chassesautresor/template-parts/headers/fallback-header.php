<?php
defined( 'ABSPATH' ) || exit;

/**
 * Template part : Header fallback immersif
 *
 * Reçoit les paramètres via `$args` depuis `get_template_part()`.
 *
 * Variables attendues :
 * - $args['titre'] : Titre principal
 * - $args['sous_titre'] : Sous-titre (optionnel)
 * - $args['image_fond'] : URL d'image de fond (optimisée en .webp par ex.)
 * - $args['logo_id'] : ID du logo à afficher (optionnel)
 */

if ( ! isset( $args ) || ! is_array( $args ) ) {
    return;
}

$titre       = isset( $args['titre'] ) ? wp_kses_post( $args['titre'] ) : '';
$sous_titre  = isset( $args['sous_titre'] ) ? esc_html( $args['sous_titre'] ) : '';
$image_url   = isset( $args['image_fond'] ) ? esc_url( $args['image_fond'] ) : '';
$logo_id     = isset( $args['logo_id'] ) ? absint( $args['logo_id'] ) : 0;
?>

<section class="bandeau-hero fallback-header">
  <div class="hero-overlay" <?php if ( $image_url ) : ?>style="background-image: url('<?php echo $image_url; ?>');"<?php endif; ?>>
    <div class="contenu-hero">
      <?php if ( $logo_id ) : ?>
        <?php echo wp_get_attachment_image( $logo_id, 'full', false, [
            'class'   => 'hero-logo',
            'loading' => 'eager',
        ] ); ?>
      <?php endif; ?>

      <?php if ( $titre ) : ?>
        <h1 class="hero-title"><?php echo $titre; ?></h1>
      <?php endif; ?>

      <?php if ( $sous_titre ) : ?>
        <p class="hero-subtitle"><?php echo $sous_titre; ?></p>
      <?php endif; ?>
    </div>
  </div>
</section>
