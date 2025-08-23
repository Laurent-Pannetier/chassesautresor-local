<?php
/**
 * Helper functions for table rendering.
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Render a table cell with excerpt and toggle for long propositions.
 *
 * @param string $text     Full text to display.
 * @param bool   $expanded Whether the cell should be expanded by default.
 * @param int    $limit    Number of characters before truncation.
 *
 * @return string HTML for the table cell.
 */
function cta_render_proposition_cell(string $text, bool $expanded = false, int $limit = 39): string
{
    $needs_toggle = mb_strlen($text) > $limit;
    $excerpt      = $needs_toggle ? mb_substr($text, 0, $limit) . '…' : $text;

    $excerpt_html = '<span class="proposition-excerpt"' . ($expanded ? ' hidden' : '') . '>' . esc_html($excerpt) . '</span>';
    $full_html    = '';
    $button_html  = '';
    $class        = 'proposition-cell';

    if ($needs_toggle) {
        $full_html = '<span class="proposition-full"' . ($expanded ? '' : ' hidden') . '>' . esc_html($text) . '</span>';

        $label_more = esc_attr__('Voir plus', 'chassesautresor-com');
        $label_less = esc_attr__('Voir moins', 'chassesautresor-com');
        $aria_label = $expanded ? $label_less : $label_more;
        $icon       = $expanded ? 'fa-minus' : 'fa-ellipsis';

        $button_html = '<button type="button" class="toggle-proposition" aria-expanded="' . ($expanded ? 'true' : 'false') . '" aria-label="' . $aria_label . '" data-more="' . $label_more . '" data-less="' . $label_less . '"><i class="fa-solid ' . $icon . '" aria-hidden="true"></i></button>';
    }

    if ($expanded && $needs_toggle) {
        $class .= ' expanded';
    }

    $content_html = '<div class="proposition-content">' . $excerpt_html . $full_html . $button_html . '</div>';

    return '<td class="' . $class . '">' . $content_html . '</td>';
}
