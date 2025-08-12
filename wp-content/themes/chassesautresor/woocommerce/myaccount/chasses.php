<?php
defined('ABSPATH') || exit;
?>

<h2><?php esc_html_e('Mes chasses', 'chassesautresor-com'); ?></h2>
<p><?php esc_html_e('Retrouvez ici vos chasses engagées et votre progression.', 'chassesautresor-com'); ?></p>
<p><?php esc_html_e('Vous n\'avez pas encore engagé de chasse.', 'chassesautresor-com'); ?></p>
<p>
    <a class="button" href="<?php echo esc_url(home_url('/chasses')); ?>">
        <?php esc_html_e('Découvrir les chasses', 'chassesautresor-com'); ?>
    </a>
</p>
