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

$args  = $args ?? [];
$icon  = $args['icon'] ?? $icon ?? '';
$label = $args['label'] ?? $label ?? '';
$value = $args['value'] ?? $value ?? '';
$stat  = $args['stat'] ?? $stat ?? '';
$style = $args['style'] ?? $style ?? '';
?>
<div class="dashboard-card" data-stat="<?= esc_attr($stat); ?>"<?php echo $style ? ' style="' . esc_attr($style) . '"' : ''; ?>>
  <i class="<?= esc_attr($icon); ?>"></i>
  <h3><?= esc_html($label); ?></h3>
  <p class="stat-value"><?= esc_html($value); ?></p>
</div>
