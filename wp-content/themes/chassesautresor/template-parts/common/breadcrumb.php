<?php
/**
 * Template part: Breadcrumb navigation.
 *
 * Expected $args['items'] as array of ['label' => string, 'url' => string|null, 'current' => bool].
 */

defined('ABSPATH') || exit;

$items = isset($args['items']) && is_array($args['items']) ? $args['items'] : [];

if (empty($items)) {
    return;
}
?>
<nav class="breadcrumb" aria-label="<?php echo esc_attr__('Fil d\'Ariane', 'chassesautresor-com'); ?>">
    <ol itemscope itemtype="https://schema.org/BreadcrumbList">
        <?php foreach ($items as $index => $item) : ?>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <?php if (!empty($item['url']) && empty($item['current'])) : ?>
                    <a itemprop="item" href="<?= esc_url($item['url']); ?>">
                        <span itemprop="name"><?= esc_html($item['label']); ?></span>
                    </a>
                <?php else : ?>
                    <span itemprop="name"><?= esc_html($item['label']); ?></span>
                <?php endif; ?>
                <meta itemprop="position" content="<?= (int) ($index + 1); ?>" />
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
