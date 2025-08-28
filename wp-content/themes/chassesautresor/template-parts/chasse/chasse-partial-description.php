<?php
defined('ABSPATH') || exit;

$description = $args['description'] ?? '';

if (!empty($description)) {
    $word_count = str_word_count(wp_strip_all_tags($description));

    if ($word_count > 200) {
        $short_description = wp_trim_words(wp_strip_all_tags($description), 200, '…');
        ?>
        <div class="chasse-description" id="chasse-description">
            <div class="description-short"><?= esc_html($short_description); ?></div>
            <div class="description-full" hidden><?= wp_kses_post($description); ?></div>
            <button
                type="button"
                class="description-toggle"
                aria-expanded="false"
                data-label-more="<?= esc_attr__('Voir plus', 'chassesautresor-com'); ?>"
                data-label-less="<?= esc_attr__('Voir moins', 'chassesautresor-com'); ?>"
            >
                <?= esc_html__('Voir plus', 'chassesautresor-com'); ?>
            </button>
        </div>
        <?php
    } else {
        ?>
        <div class="chasse-description" id="chasse-description">
            <?= wp_kses_post($description); ?>
        </div>
        <?php
    }
}
