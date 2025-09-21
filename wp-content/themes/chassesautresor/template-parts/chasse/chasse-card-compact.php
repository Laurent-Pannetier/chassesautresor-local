<?php
defined('ABSPATH') || exit;

if (!isset($args['chasse_id']) || empty($args['chasse_id'])) {
    return;
}

$chasse_id = (int) $args['chasse_id'];
$infos     = preparer_infos_affichage_carte_chasse(
    $chasse_id,
    300,
    [
        'badge_format' => 'icon',
    ]
);

if (empty($infos)) {
    return;
}

$badge_tooltip = $infos['badge_tooltip'] ?? '';
$badge_has_interaction = !empty($infos['badge_requires_interaction']);
$badge_attributes = '';

if ($badge_has_interaction && $badge_tooltip !== '') {
    $tooltip_attr = esc_attr($badge_tooltip);
    $badge_attributes .= ' aria-label="' . $tooltip_attr . '"';
    $badge_attributes .= ' title="' . $tooltip_attr . '"';
    $badge_attributes .= ' data-tooltip="' . $tooltip_attr . '"';
    $badge_attributes .= ' role="img" tabindex="0"';
}
?>
<div class="carte carte-chasse carte-compact <?php echo esc_attr($infos['classe_statut']); ?>">
    <a href="<?php echo esc_url($infos['permalink']); ?>" class="carte-compact__lien">
        <div class="carte-compact__image-wrapper">
            <span class="badge-statut <?php echo esc_attr($infos['badge_class']); ?>" data-post-id="<?php echo esc_attr($chasse_id); ?>"<?= $badge_attributes; ?>>
                <?php echo $infos['badge_content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- contenu préparé et sécurisé en amont. ?>
            </span>
            <img src="<?php echo esc_url($infos['image']); ?>" alt="<?php echo esc_attr($infos['titre']); ?>" class="carte-compact__image">
        </div>
        <div class="carte-compact__contenu">
            <h3 class="carte-compact__titre"><?php echo esc_html($infos['titre']); ?></h3>
            <?php echo $infos['lot_html']; ?>
            <?php
            get_template_part(
                'template-parts/chasse/partials/chasse-meta-row',
                null,
                array(
                    'infos'           => $infos,
                    'wrapper_class'   => 'carte-compact__meta meta-row svg-xsmall',
                    'display_mode'    => 'counts',
                    'use_short_dates' => true,
                )
            );
            ?>
        </div>
    </a>
    <?php if (!empty($args['extra_content'])) : ?>
        <?php echo $args['extra_content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe HTML generated internally. ?>
    <?php endif; ?>
</div>
