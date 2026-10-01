<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Render a hint after it has been unlocked. */
final class HintUnlockRenderer
{
    public function render(int $hintId): string
    {
        $content = get_field('indice_contenu', $hintId) ?: '';
        $text = wp_kses_post(apply_filters('the_content', $content));
        $imageId = get_field('indice_image', $hintId);
        $image = $imageId ? $this->image((int) $imageId) : '';
        $html = '<div class="indice-contenu">';

        if ($image !== '') {
            $html .= '<div class="indice-contenu__image">' . $image . '</div>';
        }

        return $html . '<div class="indice-contenu__texte">' . $text . '</div></div>';
    }

    private function image(int $imageId): string
    {
        $thumbnail = (string) wp_get_attachment_image($imageId, 'thumbnail');
        $full = wp_get_attachment_image_url($imageId, 'full');

        if (!$full) {
            return $thumbnail;
        }

        return '<a href="' . esc_url($full) . '" class="image eyebox-trigger" data-full="'
            . esc_url($full) . '">' . $thumbnail
            . '<i class="fa-solid fa-eye eyebox-icon" aria-hidden="true"></i></a>';
    }
}
