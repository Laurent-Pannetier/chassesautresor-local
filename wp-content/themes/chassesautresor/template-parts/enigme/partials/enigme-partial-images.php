<?php
defined('ABSPATH') || exit;

$post_id = $args['post_id'] ?? null;
if (!$post_id) {
    cat_debug('[images] ❌ post_id manquant dans partial');
    return;
}

// Récupération standard des images (format tableau ACF avec clés 'ID', etc.)
$images = get_field('enigme_visuel_image', $post_id);
cat_debug('[images] 🔍 Énigme #' . $post_id . ' → images récupérées : ' . print_r($images, true));

// Test : au moins une image != placeholder
$has_valid_images = is_array($images) && array_filter($images, function ($img) {
    return isset($img['ID']) && (int) $img['ID'] !== ID_IMAGE_PLACEHOLDER_ENIGME;
});

// ID de l'image principale (réelle ou placeholder)
$image_id = $has_valid_images ? (int) ($images[0]['ID'] ?? 0) : ID_IMAGE_PLACEHOLDER_ENIGME;
$meta      = wp_get_attachment_metadata($image_id);
$width     = (int) ($meta['width'] ?? 0);
$threshold_full = 1024;

if (function_exists('utilisateur_peut_voir_enigme') && !utilisateur_peut_voir_enigme($post_id)) {
    echo '<div class="visuels-proteges">🔒 Les visuels de cette énigme sont protégés.</div>';
    return;
}

if ($has_valid_images) {
    cat_debug('[images] ✅ Galerie active pour #' . $post_id);

    echo '<div class="galerie-enigme-wrapper">';

    // Image principale
    $img_attrs = [
        'id'      => 'image-enigme-active',
        'class'   => 'image-active',
        'loading' => 'lazy',
        'srcset'  => wp_get_attachment_image_srcset($image_id, 'large'),
        'sizes'   => wp_get_attachment_image_sizes($image_id, 'large'),
    ];
    if ($width && $width <= $threshold_full) {
        $img_attrs['class'] .= ' enigme-image--limited';
        $img_attrs['style']  = 'width:auto;max-width:100%;';
    } elseif ($width) {
        $img_attrs['style'] = 'width:100%;';
    }

    $img_html = wp_get_attachment_image($image_id, 'large', false, $img_attrs);
    echo '<div class="image-principale">';
    echo $img_html;
    echo '</div>';

    echo '</div>';
} else {
    cat_debug('[images] 🟡 Aucune image valide → fallback picture');
    $attrs = [
        'srcset' => wp_get_attachment_image_srcset(ID_IMAGE_PLACEHOLDER_ENIGME, 'large'),
        'sizes'   => wp_get_attachment_image_sizes(ID_IMAGE_PLACEHOLDER_ENIGME, 'large'),
        'loading' => 'lazy',
        'alt'     => esc_attr__('Image par défaut de l’énigme', 'chassesautresor-com'),
    ];

    if ($width && $width <= $threshold_full) {
        $attrs['class'] = 'enigme-image--limited';
        $attrs['style'] = 'width:auto;max-width:100%;';
    } elseif ($width) {
        $attrs['style'] = 'width:100%;';
    }

    echo '<div class="image-principale">';
    echo wp_get_attachment_image(ID_IMAGE_PLACEHOLDER_ENIGME, 'large', false, $attrs);
    echo '</div>';
}

