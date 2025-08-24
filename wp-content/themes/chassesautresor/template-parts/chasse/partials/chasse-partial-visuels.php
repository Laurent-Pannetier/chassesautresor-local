<?php
/**
 * Visual share block for a hunt.
 *
 * Variables:
 * - $chasse_id (int)
 *
 * @package chassesautresor.com
 */

defined('ABSPATH') || exit;

$args      = $args ?? [];
$chasse_id = $args['chasse_id'] ?? 0;
$visuels   = chasse_get_visuels_data($chasse_id);
?>
<div class="visuel-web">
    <?php if ($visuels['image']) : ?>
        <img class="visuel-image" src="<?= esc_url($visuels['image']); ?>" alt="">
    <?php endif; ?>
    <div class="visuel-contenu">
        <?php if ($visuels['logo']) : ?>
            <img class="visuel-logo" src="<?= esc_url($visuels['logo']); ?>" alt="">
        <?php endif; ?>
        <h4 class="visuel-titre"><?= esc_html($visuels['titre']); ?></h4>
        <p class="visuel-description" id="visuels-description"><?= esc_html($visuels['description']); ?></p>
        <button
            type="button"
            class="copy-message"
            data-target="#visuels-description"
            aria-label="<?= esc_attr__('Copier', 'chassesautresor-com'); ?>"
        >
            <?= esc_html__('Copier', 'chassesautresor-com'); ?>
        </button>
        <?= $visuels['liens']; ?>
        <img class="visuel-qr" src="<?= esc_url($visuels['qr']); ?>" alt="QR">
    </div>
</div>
<p class="visuel-texte" id="visuels-texte"><?= esc_html($visuels['texte']); ?></p>
<button
    type="button"
    class="copy-message"
    data-target="#visuels-texte"
    aria-label="<?= esc_attr__('Copier', 'chassesautresor-com'); ?>"
>
    <?= esc_html__('Copier', 'chassesautresor-com'); ?>
</button>
<div class="visuel-message-complet" id="visuels-complet">
    <p><?= esc_html($visuels['texte']); ?></p>
    <img class="visuel-image" src="<?= esc_url($visuels['image']); ?>" alt="">
    <img class="visuel-qr" src="<?= esc_url($visuels['qr']); ?>" alt="QR">
    <?= $visuels['liens']; ?>
</div>
<button
    type="button"
    class="copy-message"
    data-target="#visuels-complet"
    aria-label="<?= esc_attr__('Copier', 'chassesautresor-com'); ?>"
>
    <?= esc_html__('Copier', 'chassesautresor-com'); ?>
</button>
<p class="visuel-qr-brut">
    <a href="<?= esc_url($visuels['qr']); ?>" target="_blank" rel="noopener">
        <?= esc_html__('QR code brut', 'chassesautresor-com'); ?>
    </a>
</p>
