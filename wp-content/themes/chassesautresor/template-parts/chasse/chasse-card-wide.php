<?php
defined('ABSPATH') || exit;

if (!isset($args['chasse_id']) || empty($args['chasse_id'])) {
    return;
}

$chasse_id = (int) $args['chasse_id'];

$orga_id = get_organisateur_from_chasse($chasse_id);
$logo_url = $orga_id ? get_the_post_thumbnail_url($orga_id, 'thumbnail') : '';
$orga_title = $orga_id ? get_the_title($orga_id) : '';
$orga_link = $orga_id ? get_permalink($orga_id) : '';

if ($orga_id && $logo_url) {
    echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($orga_title) . '"> ';
    echo '<a href="' . esc_url($orga_link) . '">' . esc_html($orga_title) . '</a> ';
    echo esc_html__('présente', 'chassesautresor-com');
}

