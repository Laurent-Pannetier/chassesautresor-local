<?php
defined('ABSPATH') || exit;

$post_id = $args['post_id'] ?? null;
if (!$post_id) {
    cat_debug("[images] ❌ post_id manquant dans partial");
    return;
}

// Récupération standard des images (format tableau ACF avec clés 'ID', etc.)
$images = get_field('enigme_visuel_image', $post_id);
cat_debug("[images] 🔍 Énigme #$post_id → images récupérées : " . print_r($images, true));

// Test : au moins une image != placeholder
$has_valid_images = is_array($images) && array_filter($images, function ($img) {
    return isset($img['ID']) && (int) $img['ID'] !== ID_IMAGE_PLACEHOLDER_ENIGME;
});

if ($has_valid_images) {
    cat_debug("[images] ✅ Affichage empilé pour #$post_id");
    ?>
    <div class="enigme-images">
        <?php
        foreach ($images as $img) {
            $id = $img['ID'] ?? null;
            if (!$id || (int) $id === ID_IMAGE_PLACEHOLDER_ENIGME) {
                continue;
            }

            echo '<figure class="enigme-image">';
            echo build_picture_enigme($id, __('Visuel énigme', 'chassesautresor-com'), ['thumbnail', 'medium', 'large', 'full']);
            echo '</figure>';
        }
        ?>
    </div>
    <?php
} else {
    cat_debug("[images] 🟡 Aucune image valide → fallback picture");
    ?>
    <div class="image-principale">
        <?php
        echo wp_get_attachment_image(
            ID_IMAGE_PLACEHOLDER_ENIGME,
            'large',
            false,
            [
                'srcset' => wp_get_attachment_image_srcset(ID_IMAGE_PLACEHOLDER_ENIGME, 'large'),
                'sizes' => wp_get_attachment_image_sizes(ID_IMAGE_PLACEHOLDER_ENIGME, 'large'),
                'loading' => 'lazy',
                'alt' => esc_attr__('Image par défaut de l’énigme', 'chassesautresor-com'),
            ]
        );
        ?>
    </div>
    <?php
}

