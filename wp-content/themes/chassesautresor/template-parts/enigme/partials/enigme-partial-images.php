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
$meta = wp_get_attachment_metadata($image_id);
$width = (int) ($meta['width'] ?? 0);
$threshold_full = 1024;

if ($has_valid_images && function_exists('afficher_visuels_enigme')) {
    cat_debug('[images] ✅ Galerie active pour #' . $post_id);

    ob_start();
    afficher_visuels_enigme($post_id);
    $html = ob_get_clean();

    if ($width && $width <= $threshold_full) {
        $html = preg_replace('/<img([^>]*?)style="([^"]*)"/i', '<img$1style="$2width:auto;max-width:100%;"', $html, 1, $c);
        if (0 === $c) {
            $html = preg_replace('/<img/i', '<img style="width:auto;max-width:100%;"', $html, 1);
        }
        $html = preg_replace('/<img([^>]*?)class="([^"]*)"/i', '<img$1class="$2 enigme-image--limited"', $html, 1, $c);
        if (0 === $c) {
            $html = preg_replace('/<img/i', '<img class="enigme-image--limited"', $html, 1);
        }
    } elseif ($width) {
        $html = preg_replace('/<img([^>]*?)style="([^"]*)"/i', '<img$1style="$2width:100%;"', $html, 1, $c);
        if (0 === $c) {
            $html = preg_replace('/<img/i', '<img style="width:100%;"', $html, 1);
        }
    }

    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

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

