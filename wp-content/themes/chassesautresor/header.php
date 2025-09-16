<?php
/**
 * The header for Astra Theme.
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Astra
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$hero_image_url = '';

if ( is_front_page() ) {
    $hero_image_url = imagify_get_webp_url( wp_get_attachment_image_url( 8810, 'full' ) );
} elseif ( is_page() && ! is_user_account_area() ) {
    $featured_image_id = get_post_thumbnail_id();

    if ( $featured_image_id ) {
        $hero_image_url = imagify_get_webp_url( wp_get_attachment_image_url( $featured_image_id, 'full' ) );
    }
}

?><!DOCTYPE html>
<?php astra_html_before(); ?>
<html <?php language_attributes(); ?>>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-GGEM813SKQ"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-GGEM813SKQ');
</script>

<head>
<?php astra_head_top(); ?>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php if ( $hero_image_url ) : ?>
<link rel="preload" as="image" href="<?php echo esc_url( $hero_image_url ); ?>">
<?php endif; ?>
<?php
if ( apply_filters( 'astra_header_profile_gmpg_link', true ) ) {
        ?>
        <link rel="profile" href="https://gmpg.org/xfn/11">
	<?php
}
?>
<?php wp_head(); ?>
<?php astra_head_bottom(); ?>
</head>

<body <?php astra_schema_body(); ?> <?php body_class(); ?>>
<?php astra_body_top(); ?>
<?php wp_body_open(); ?>

<a
        class="skip-link screen-reader-text"
        href="#content"
        title="<?php echo esc_attr( astra_default_strings( 'string-header-skip-link', false ) ); ?>">
                <?php echo esc_html( astra_default_strings( 'string-header-skip-link', false ) ); ?>
</a>

<div
<?php
        echo wp_kses_post(
                astra_attr(
			'site',
			array(
				'id'    => 'page',
				'class' => 'hfeed site',
			)
		)
	);
	?>
>
	<?php
	astra_header_before();

	astra_header(); 
	?>

    <?php
    astra_header_after();
    
    // ==================================================
    // 🧩 HEADER VISUEL (selon contexte)
    // ==================================================
    if ( is_cart() ) {
        get_template_part('template-parts/header-panier');
    } elseif ( is_front_page() ) {
        $line1 = sprintf(
            /* translators: 1: ordinal suffix, 2: phrase 'plateforme de'. */
            __( '1<sup>%1$s</sup> %2$s', 'chassesautresor-com' ),
            esc_html__( 'ère', 'chassesautresor-com' ),
            esc_html__( 'plateforme de', 'chassesautresor-com' )
        );

        $titre = sprintf(
            '<span class="hero-title__line1">%1$s</span><span class="hero-title__line2">%2$s</span>',
            wp_kses_post( $line1 ),
            esc_html__( 'chasses au trésor', 'chassesautresor-com' )
        );

        get_header_fallback([
            'titre'      => $titre,
            'sous_titre' => '',
            'image_fond' => $hero_image_url,
            'logo_id'    => 475,
        ]);
    } elseif ( is_page() && ! is_user_account_area() ) {
        get_header_fallback([
            'titre'       => get_the_title(),
            'sous_titre'  => '',
            'image_fond'  => $hero_image_url,
        ]);
    }
    
    astra_content_before();
    ?>

	
        <div id="content" class="site-content">
                <div class="ast-container<?php echo ( is_singular('enigme') || is_singular('chasse') ) ? '' : ' ast-container--boxed'; ?>">
                <?php astra_content_top(); ?>
                <?php if (!is_page_template('templates/page-devenir-organisateur.php')) : ?>
                <section class="msg-important"><?php print_site_messages(); ?></section>
                <?php endif; ?>
