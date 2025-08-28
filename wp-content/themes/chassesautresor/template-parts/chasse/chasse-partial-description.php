<?php
defined('ABSPATH') || exit;

$description = $args['description'] ?? '';
?>


<section class="chasse-description-section bloc-elegant" id="chasse-description">
    <?php if (!empty($description)) : ?>
        <div class="chasse-description">
            <?= wp_kses_post($description); ?>
        </div>
    <?php endif; ?>
</section>
