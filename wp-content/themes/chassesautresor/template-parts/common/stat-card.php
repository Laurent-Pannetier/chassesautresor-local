<?php
/**
 * Generic dashboard stat card.
 *
 * Variables required:
 * - $icon  (string) Font Awesome classes for the icon.
 * - $label (string) Card title.
 * - $value (int|string) Value to display.
 * - $stat  (string) Data attribute identifier.
 * - $style (string, optional) Inline style attribute.
 */

defined('ABSPATH') || exit;

$args       = $args ?? [];
$icon       = $args['icon'] ?? $icon ?? '';
$label      = $args['label'] ?? $label ?? '';
$value      = $args['value'] ?? $value ?? '';
$stat       = $args['stat'] ?? $stat ?? '';
$style      = $args['style'] ?? $style ?? '';
$help       = $args['help'] ?? $help ?? '';
$help_label = $args['help_label'] ?? $help_label ?? '';
?>
<div class="dashboard-card" data-stat="<?= esc_attr($stat); ?>"<?php echo $style ? ' style="' . esc_attr($style) . '"' : ''; ?>>
  <i class="<?= esc_attr($icon); ?>"></i>
  <h3>
    <?= esc_html($label); ?>
    <?php if ($help) : ?>
      <button type="button" class="mode-fin-aide stat-help" data-message="<?= esc_attr($help); ?>"<?php echo $help_label ? ' aria-label="' . esc_attr($help_label) . '"' : ''; ?>>
        <i class="fa-regular fa-circle-question" aria-hidden="true"></i>
      </button>
    <?php endif; ?>
  </h3>
  <p class="stat-value"><?= esc_html($value); ?></p>
</div>
