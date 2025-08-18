<?php
/**
 * Reusable help icon button component.
 *
 * @param string $aria_label  ARIA label for the button.
 * @param string $message     Optional help message (added as data-message attribute).
 * @param string $classes     CSS classes to apply on the button.
 * @param array  $attributes  Additional HTML attributes for the button.
 */

defined('ABSPATH') || exit;

$args = wp_parse_args(
    $args ?? [],
    [
        'aria_label' => '',
        'message'    => '',
        'classes'    => '',
        'attributes' => [],
    ]
);

$aria_label = $args['aria_label'];
$message    = $args['message'];
$classes    = $args['classes'];
$attributes = $args['attributes'];

if ($message !== '') {
    $attributes['data-message'] = $message;
}

$attr_string = '';
if ($aria_label !== '') {
    $attr_string .= ' aria-label="' . esc_attr($aria_label) . '"';
}
foreach ($attributes as $attr => $value) {
    $attr_string .= ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
}
?>
<button type="button" class="<?= esc_attr($classes); ?>"<?= $attr_string; ?>>
    <i class="fa-regular fa-circle-question" aria-hidden="true"></i>
</button>
