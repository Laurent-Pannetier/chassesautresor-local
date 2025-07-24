<?php
/**
 * Template Name: Mon Compte
 * Description: Mise en page unifiée pour l'espace utilisateur.
 */

defined('ABSPATH') || exit;

add_filter('body_class', function ($classes) {
    $classes[] = 'has-hero';
    return $classes;
});

$image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'full') : '';

get_header();
?>
<section class="bandeau-hero">
  <div class="hero-overlay" style="background-image:url('<?php echo esc_url($image_url); ?>');">
    <div class="contenu-hero">
      <h1><?php the_title(); ?></h1>
    </div>
  </div>
</section>
<?php get_template_part('template-parts/myaccount/important-messages'); ?>
<main id="primary" class="site-main">
<?php
while (have_posts()) :
    the_post();
    the_content();
endwhile;
?>
</main>
<?php get_footer(); ?>
