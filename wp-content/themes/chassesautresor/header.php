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
<style id="cta-critical-css">
:root {
    --font-main: "Poppins", sans-serif;
    --color-primary: #FFD700;
    --color-secondary: #E1A95F;
    --color-text-primary: #F5F5DC;
    --color-background: #0B132B;
    --space-xxs: 0.25rem;
    --space-xs: 0.5rem;
    --space-sm: 0.75rem;
    --space-md: 1rem;
    --space-lg: 1.25rem;
    --space-xl: 1.5rem;
    --space-2xl: 2rem;
    --space-3xl: 2.5rem;
    --container-max-width: 1200px;
}

body {
    margin: 0;
    font-family: var(--font-main, sans-serif);
    background-color: var(--color-background, #0B132B);
    color: var(--color-text-primary, #F5F5DC);
    line-height: 1.6;
}

a {
    color: var(--color-secondary, #E1A95F);
    text-decoration: none;
}

a:hover,
a:focus {
    color: var(--color-primary, #FFD700);
}

.site {
    min-height: 100vh;
    background-color: var(--color-background, #0B132B);
}

.site-header {
    position: relative;
    z-index: 20;
}

.main-header-bar,
.site-header {
    background: rgba(11, 19, 43, 0.85);
    backdrop-filter: blur(12px);
}

.main-header-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-sm);
    padding: var(--space-sm) var(--space-md);
}

.ast-container {
    width: 100%;
    margin-left: auto;
    margin-right: auto;
    padding-left: var(--space-md);
    padding-right: var(--space-md);
}

.ast-builder-grid-row,
.main-header-bar-wrap,
.site-branding {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.lang-switcher {
    position: relative;
    display: inline-block;
    margin: 0;
    padding: 0;
}

.lang-switcher__toggle {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    background: transparent;
    border: 1px solid rgba(245, 245, 220, 0.35);
    border-radius: 999px;
    color: inherit;
    cursor: pointer;
}

.lang-switcher__icon {
    font-size: 0.75rem;
}

.lang-switcher__flag {
    font-size: 1rem;
    line-height: 1;
}

.lang-switcher__options {
    position: absolute;
    top: 110%;
    right: 0;
    min-width: 7rem;
    padding: var(--space-xxs) 0;
    margin: 0;
    list-style: none;
    background: rgba(11, 19, 43, 0.95);
    border-radius: 8px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: opacity 0.2s ease, transform 0.2s ease;
    z-index: 30;
}

.lang-switcher__options li {
    margin: 0;
}

.lang-switcher__options li a,
.lang-switcher__options li span {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.8rem;
    color: inherit;
}

.lang-switcher__options li.active span {
    font-weight: 600;
}

.lang-switcher__toggle[aria-expanded="true"] + .lang-switcher__options {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.lang-switcher.is-open .lang-switcher__options {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.has-hero #content {
    position: relative;
    padding-top: 235px;
}

.bandeau-hero {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    padding: var(--space-md);
    z-index: 10;
}

.bandeau-hero::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 60px;
    background: linear-gradient(to bottom, transparent, var(--color-background, #0B132B));
    pointer-events: none;
}

.hero-overlay {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 220px;
    padding: var(--space-2xl) var(--space-md);
    border-radius: 12px;
    background-color: rgba(0, 0, 0, 0.55);
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    text-align: center;
}

.contenu-hero {
    max-width: 720px;
    margin: 0 auto;
    opacity: 1;
    transform: none;
}

.hero-logo {
    max-width: 150px;
    margin-bottom: var(--space-sm);
}

.hero-title {
    margin: 0 0 var(--space-md);
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--color-primary, #FFD700);
    text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.7);
}

.hero-title__line1,
.hero-title__line2 {
    display: block;
    text-transform: none;
}

.hero-title__line1 {
    font-size: 1rem;
    font-weight: 400;
}

.hero-title__line2 {
    font-size: 1.5rem;
    font-weight: 700;
}

.hero-subtitle {
    margin: 0 auto var(--space-xs);
    max-width: 700px;
    font-size: 1rem;
    color: var(--color-text-primary, #F5F5DC);
    opacity: 0.95;
    text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.7);
}

@media (min-width: 480px) {
    .hero-overlay {
        min-height: 280px;
    }

    .hero-logo {
        max-width: 200px;
    }

    .hero-title {
        font-size: 1.8rem;
    }

    .hero-title__line1 {
        font-size: 1.2rem;
    }

    .hero-title__line2 {
        font-size: 2rem;
    }

    .hero-subtitle {
        font-size: 1.3rem;
        margin-bottom: var(--space-lg);
    }

    .has-hero #content {
        padding-top: 300px;
    }
}

@media (min-width: 768px) {
    .main-header-bar {
        padding: var(--space-md) var(--space-2xl);
    }

    .ast-container {
        max-width: var(--container-max-width, 1200px);
        padding-left: var(--space-2xl);
        padding-right: var(--space-2xl);
    }

    .hero-overlay {
        min-height: 475px;
        padding: var(--space-3xl);
    }

    .hero-title {
        font-size: 2.6rem;
    }

    .hero-title__line1 {
        font-size: 1.4rem;
    }

    .hero-title__line2 {
        font-size: 2.6rem;
    }

    .has-hero #content {
        padding-top: 475px;
    }
}
</style>
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
        $image_url = imagify_get_webp_url( wp_get_attachment_image_url( 8810, 'full' ) );

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
            'image_fond' => $image_url,
            'logo_id'    => 475,
        ]);
    } elseif ( is_page() && ! is_user_account_area() ) {
        $image_id  = get_post_thumbnail_id();
        $image_url = $image_id ? imagify_get_webp_url( wp_get_attachment_image_url( $image_id, 'full' ) ) : '';

        get_header_fallback([
            'titre'       => get_the_title(),
            'sous_titre'  => '',
            'image_fond'  => $image_url,
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
