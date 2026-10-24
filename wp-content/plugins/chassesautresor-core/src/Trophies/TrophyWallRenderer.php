<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Render the staff trophy wall. A count opens the holder list. */
final class TrophyWallRenderer
{
    /** @param array<int, array<string, mixed>> $cards */
    public function render(array $cards): string
    {
        if ($cards === []) {
            return '';
        }

        ob_start();
        echo '<div class="trophies-grid trophies-wall">';
        foreach ($cards as $card) {
            $this->card($card);
        }
        echo '</div>';

        return trim((string) ob_get_clean());
    }

    /** @param array<string, mixed> $card */
    private function card(array $card): void
    {
        $count = (int) ($card['count'] ?? 0);
        $context = trim((string) ($card['context_label'] ?? ''));
        $editUrl = (string) ($card['edit_url'] ?? '');
        $class = $count > 0 ? 'trophy-card trophy-card--held' : 'trophy-card trophy-card--none';
        if ($context !== '' || $editUrl !== '') {
            $class .= ' trophy-card--labeled';
        }
        $title = (string) ($card['title'] ?? '');
        $description = trim((string) ($card['description'] ?? ''));
        $imageUrl = (string) ($card['image_url'] ?? '');
        $holders = array_values(array_filter(array_map(
            static fn ($name): string => trim((string) $name),
            (array) ($card['holders'] ?? [])
        )));

        echo '<article class="' . esc_attr($class) . '">';
        if ($editUrl !== '') {
            echo '<a class="trophy-card__gear" href="' . esc_url($editUrl) . '" aria-label="'
                . esc_attr__('Paramètres', 'chassesautresor-com') . '">';
            echo '<i class="fa-solid fa-gear" aria-hidden="true"></i>';
            echo '</a>';
        }
        if ($imageUrl !== '') {
            echo '<img class="trophy-card__image" src="' . esc_url($imageUrl) . '" alt="">';
        } else {
            echo '<div class="trophy-card__fallback" aria-hidden="true">🏆</div>';
        }
        echo '<div class="trophy-card__content">';
        echo '<h2>' . esc_html($title) . '</h2>';
        if ($description !== '') {
            echo '<p>' . esc_html($description) . '</p>';
        }
        if ($count > 0 && $holders !== []) {
            echo '<details class="trophy-card__details">';
            echo '<summary class="trophy-card__count">' . $count . '</summary>';
            echo '<ul class="trophy-card__holders">';
            foreach ($holders as $holder) {
                echo '<li>' . esc_html($holder) . '</li>';
            }
            echo '</ul></details>';
        }
        echo '</div>';
        if ($context !== '') {
            echo '<p class="trophy-card__banner">' . esc_html($context) . '</p>';
        }
        echo '</article>';
    }
}
