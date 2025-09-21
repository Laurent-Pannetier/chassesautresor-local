<?php
/**
 * Compact card format for hunts (CART).
 * Image on top, title, and meta footer.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

if (!isset($args['chasse_id']) || empty($args['chasse_id'])) {
    return;
}

$chasse_id       = (int) $args['chasse_id'];
$completion_class = $args['completion_class'] ?? '';
$infos           = preparer_infos_affichage_carte_chasse($chasse_id);

if (empty($infos)) {
    return;
}

$badge_tooltip = $infos['badge_tooltip'] ?? '';
$badge_icon_html = $infos['statut_icon'] ?? '';
$badge_label = $infos['statut_label'] ?? ($infos['badge_content'] ?? '');
$has_badge_icon = $badge_icon_html !== '';
$badge_classes = $infos['badge_class'] ?? '';
$image_ratio = $infos['image_ratio'] ?? '';
$image_style = $image_ratio !== '' ? '--carte-cart-aspect-ratio:' . $image_ratio . ';' : '';
$image_html = '';

if (!empty($infos['image_id'])) {
    $image_attributes = [
        'class'   => 'carte-cart__image',
        'alt'     => $infos['titre'],
        'loading' => 'lazy',
    ];

    if ($image_style !== '') {
        $image_attributes['style'] = $image_style;
    }

    $image_html = wp_get_attachment_image(
        (int) $infos['image_id'],
        'medium',
        false,
        $image_attributes
    );
}

if ($image_html === '') {
    $style_attribute = $image_style !== '' ? ' style="' . esc_attr($image_style) . '"' : '';
    $image_html = sprintf(
        '<img src="%1$s" alt="%2$s" class="carte-cart__image" loading="lazy"%3$s>',
        esc_url($infos['image']),
        esc_attr($infos['titre']),
        $style_attribute
    );
}

if ($has_badge_icon) {
    $badge_classes = trim($badge_classes . ' badge-statut--responsive');
}

$badge_has_interaction = $has_badge_icon && $badge_tooltip !== '';
$badge_attributes = '';

if ($badge_has_interaction && $badge_tooltip !== '') {
    $tooltip_attr = esc_attr($badge_tooltip);
    $badge_attributes .= ' aria-label="' . $tooltip_attr . '"';
    $badge_attributes .= ' title="' . $tooltip_attr . '"';
    $badge_attributes .= ' data-tooltip="' . $tooltip_attr . '"';
    $badge_attributes .= ' role="img" tabindex="0"';
}
?>
<div class="carte carte-chasse carte-cart <?php echo esc_attr(trim($infos['classe_statut'] . ' ' . $completion_class)); ?>">
    <a href="<?php echo esc_url($infos['permalink']); ?>" class="carte-cart__lien">
        <div class="carte-cart__image-wrapper">
            <span class="badge-statut <?php echo esc_attr($badge_classes); ?>" data-post-id="<?php echo esc_attr($chasse_id); ?>"<?= $badge_attributes; ?>>
                <span class="badge-statut__label"><?php echo esc_html($badge_label); ?></span>
                <?php if ($has_badge_icon) : ?>
                    <span class="badge-statut__icon" aria-hidden="true">
                        <?php echo $badge_icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- contenu préparé et sécurisé en amont. ?>
                    </span>
                <?php endif; ?>
            </span>
            <?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- contenu préparé ci-dessus. ?>
        </div>
        <div class="carte-cart__contenu">
            <h3 class="carte-cart__titre"><?php echo esc_html($infos['titre']); ?></h3>
            <?php echo $infos['lot_html']; ?>
        </div>
    </a>
    <div class="carte-cart__footer meta-row svg-xsmall">
        <div class="meta-regular">
            <button type="button" class="meta-indic" data-tap="<?= esc_attr__('nombre d\'énigmes', 'chassesautresor-com'); ?>">
                <?php echo get_svg_icon('enigme'); ?>
                <span class="meta-indic__count"><?php echo esc_html(number_format_i18n($infos['total_enigmes'])); ?></span>
            </button>
            <button type="button" class="meta-indic" data-tap="<?= esc_attr__('nombre de joueurs', 'chassesautresor-com'); ?>">
                <?php echo get_svg_icon('participants'); ?>
                <span class="meta-indic__count"><?php echo esc_html(number_format_i18n($infos['nb_joueurs'])); ?></span>
            </button>
        </div>
        <div class="meta-etiquette">
            <?php echo get_svg_icon('calendar'); ?>
            <span class="chasse-date-plage">
                <span class="date-debut"><?php echo esc_html($infos['date_debut_court']); ?></span> –
                <span class="date-fin"><?php echo esc_html($infos['date_fin_court']); ?></span>
            </span>
        </div>
    </div>
</div>
