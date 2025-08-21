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

<?php
$active_locale = cta_get_locale_from_cookie();
if ( ! $active_locale ) {
    $active_locale = get_locale();
}

$available_langs = [
    'fr_FR' => [
        'code'  => 'fr',
        'label' => __( 'Français', 'chassesautresor-com' ),
        'flag'  => '🇫🇷',
    ],
    'en_US' => [
        'code'  => 'en',
        'label' => __( 'English', 'chassesautresor-com' ),
        'flag'  => '🇬🇧',
    ],
];

$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$current_url = remove_query_arg( 'lang', $current_url );
?>
<details class="lang-switcher">
    <summary>
        <span class="lang-switcher__flag">
            <?php echo esc_html( $available_langs[ $active_locale ]['flag'] ?? '🇫🇷' ); ?>
        </span>
        <span class="lang-switcher__icon">▼</span>
    </summary>
    <ul class="lang-switcher__options">
        <?php foreach ( $available_langs as $locale => $data ) : ?>
            <?php $url = add_query_arg( 'lang', $data['code'], $current_url ); ?>
            <li class="<?php echo $locale === $active_locale ? 'active' : ''; ?>">
                <?php if ( $locale === $active_locale ) : ?>
                    <span>
                        <span class="lang-switcher__flag"><?php echo esc_html( $data['flag'] ); ?></span>
                        <span class="lang-switcher__label"><?php echo esc_html( $data['label'] ); ?></span>
                    </span>
                <?php else : ?>
                    <a href="<?php echo esc_url( $url ); ?>">
                        <span class="lang-switcher__flag"><?php echo esc_html( $data['flag'] ); ?></span>
                        <span class="lang-switcher__label"><?php echo esc_html( $data['label'] ); ?></span>
                    </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</details>

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
    }
    elseif ( is_page() && ! is_user_account_area() ) {
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
                <div class="ast-container<?php echo is_singular('enigme') ? ' container--xl-full' : ''; ?>">
                <?php astra_content_top(); ?>
