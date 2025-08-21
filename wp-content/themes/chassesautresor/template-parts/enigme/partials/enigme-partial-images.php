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

// Filtrage des images valides (hors placeholder)
$valid_images = [];
if (is_array($images)) {
    foreach ($images as $img) {
        $id = (int) ($img['ID'] ?? 0);
        if ($id && $id !== ID_IMAGE_PLACEHOLDER_ENIGME) {
            $valid_images[] = $id;
        }
    }
}
if (!$valid_images) {
    $valid_images[] = ID_IMAGE_PLACEHOLDER_ENIGME;
}

$threshold_full = 1024;
$caption        = (string) get_field('enigme_visuel_legende', $post_id);

if (function_exists('utilisateur_peut_voir_enigme') && !utilisateur_peut_voir_enigme($post_id)) {
    echo '<div class="visuels-proteges">🔒 Les visuels de cette énigme sont protégés.</div>';
    return;
}

cat_debug('[images] ✅ Galerie active pour #' . $post_id);

echo '<div class="galerie-enigme-wrapper">';
foreach ($valid_images as $index => $image_id) {
    $meta  = wp_get_attachment_metadata($image_id);
    $width = (int) ($meta['width'] ?? 0);

    $attrs = [
        'loading' => 'lazy',
        'srcset'  => wp_get_attachment_image_srcset($image_id, 'large'),
        'sizes'   => wp_get_attachment_image_sizes($image_id, 'large'),
    ];
    if ($index === 0) {
        $attrs['id']    = 'image-enigme-active';
        $attrs['class'] = 'image-active';
    }
    if ($width && $width <= $threshold_full) {
        $attrs['class'] = ($attrs['class'] ?? '') . ' enigme-image--limited';
        $attrs['style'] = 'width:auto;max-width:100%;';
    } elseif ($width) {
        $attrs['style'] = 'width:100%;';
    }

    $alt = trim((string) get_post_meta($image_id, '_wp_attachment_image_alt', true));
    if (!$alt) {
        $alt = $image_id === ID_IMAGE_PLACEHOLDER_ENIGME
            ? __('Image par défaut de l’énigme', 'chassesautresor-com')
            : ($caption ?: __('Image de l’énigme', 'chassesautresor-com'));
    }
    $attrs['alt'] = esc_attr($alt);

    echo '<figure class="image-principale">';
    echo wp_get_attachment_image($image_id, 'large', false, $attrs);
    if ($caption) {
        echo '<figcaption>' . esc_html($caption) . '</figcaption>';
    }
    echo '</figure>';
}
echo '</div>';

