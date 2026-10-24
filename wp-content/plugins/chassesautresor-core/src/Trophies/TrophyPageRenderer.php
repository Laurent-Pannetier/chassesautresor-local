<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Render trophies already obtained by the current player or active team. */
final class TrophyPageRenderer
{
    /** @param array<int, array<string, mixed>> $trophies */
    public function render(array $trophies): string
    {
        if ($trophies === []) {
            return '<p class="trophies-page__empty">'
                . esc_html__('Vous n’avez encore obtenu aucun trophée.', 'chassesautresor-com')
                . '</p>';
        }

        ob_start();
        echo '<div class="trophies-grid trophies-grid--player">';
        foreach ($trophies as $trophy) {
            $this->card($trophy);
        }
        echo '</div>';

        return trim((string) ob_get_clean());
    }

    /** @param array<string, mixed> $trophy */
    private function card(array $trophy): void
    {
        $title = (string) ($trophy['title'] ?? '');
        $imageUrl = (string) ($trophy['image_url'] ?? '');
        $description = trim((string) ($trophy['description'] ?? ''));
        $types = array_map('strval', (array) ($trophy['recipient_types'] ?? []));

        echo '<article class="trophy-card trophy-card--square">';
        if ($imageUrl !== '') {
            echo '<img class="trophy-card__image" src="' . esc_url($imageUrl) . '" alt="">';
        } else {
            echo '<div class="trophy-card__fallback" aria-hidden="true">🏆</div>';
        }
        echo '<div class="trophy-card__content">';
        echo '<p class="trophy-card__scope">' . esc_html($this->scopeLabel($types)) . '</p>';
        echo '<h2>' . esc_html($title) . '</h2>';
        if ($description !== '') {
            echo '<p>' . esc_html($description) . '</p>';
        }
        echo '</div></article>';
    }

    /** @param string[] $types */
    private function scopeLabel(array $types): string
    {
        if (in_array('user', $types, true) && in_array('team', $types, true)) {
            return __('Personnel et équipe', 'chassesautresor-com');
        }
        if (in_array('team', $types, true)) {
            return __('Trophée d’équipe', 'chassesautresor-com');
        }

        return __('Trophée personnel', 'chassesautresor-com');
    }
}
