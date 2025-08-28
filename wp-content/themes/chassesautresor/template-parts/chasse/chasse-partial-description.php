<?php
defined('ABSPATH') || exit;

$description = $args['description'] ?? '';

if (!empty($description)) :
    ?>
    <div class="chasse-description" id="chasse-description">
        <?= wp_kses_post($description); ?>
    </div>
    <?php
endif;
