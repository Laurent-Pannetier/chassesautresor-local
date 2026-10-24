<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Short trophy form shown on the administrator wall. */
final class TrophyCreateFormRenderer
{
    /**
     * @param array<int, array{id: int, title: string}> $hunts
     */
    public function render(
        string $action,
        string $nonceField,
        array $hunts,
        bool $open,
        string $notice
    ): string {
        $message = $this->notice($notice);
        ob_start();
        echo '<details class="trophy-create"' . ($open ? ' open' : '') . '>';
        echo '<summary class="trophy-create__toggle">' . esc_html__('Ajouter', 'chassesautresor-com') . '</summary>';
        echo '<form class="trophy-create__form" method="post" enctype="multipart/form-data" action="'
            . esc_url($action) . '">';
        echo '<input type="hidden" name="action" value="cat_trophy_create">';
        echo $nonceField; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        if ($message !== '') {
            echo '<p class="trophy-create__notice">' . esc_html($message) . '</p>';
        }
        $this->field(
            'trophee_titre',
            __('Titre', 'chassesautresor-com'),
            '<input id="trophee_titre" name="trophee_titre" type="text" maxlength="190" required>'
        );
        $this->field(
            'trophee_texte',
            __('Texte court', 'chassesautresor-com'),
            '<textarea id="trophee_texte" name="trophee_texte" rows="3" maxlength="300"></textarea>'
        );
        $this->field(
            'trophee_image',
            __('Image', 'chassesautresor-com'),
            '<input id="trophee_image" name="trophee_image" type="file"'
                . ' accept="image/jpeg,image/png,image/gif,image/webp">'
        );
        $this->field('trophee_chasse', __('Chasse', 'chassesautresor-com'), $this->hunts($hunts));
        $this->field('trophee_attribution', __('Attribution', 'chassesautresor-com'), $this->scopes());
        $this->field(
            'trophee_cle',
            __('Clé de l’événement', 'chassesautresor-com'),
            '<input id="trophee_cle" name="trophee_cle" type="text" maxlength="120" placeholder="bienvenue" required>'
        );
        echo '<div class="trophy-create__actions">';
        echo '<button class="trophy-create__cancel" type="button" data-trophy-create-cancel>';
        echo esc_html__('Annuler', 'chassesautresor-com');
        echo '</button>';
        echo '<button class="trophy-create__submit" type="submit">';
        echo esc_html__('Ajouter', 'chassesautresor-com');
        echo '</button>';
        echo '</div></form></details>';

        return trim((string) ob_get_clean());
    }

    private function field(string $id, string $label, string $control): void
    {
        echo '<label for="' . esc_attr($id) . '">' . esc_html($label);
        echo $control; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</label>';
    }

    /** @param array<int, array{id: int, title: string}> $hunts */
    private function hunts(array $hunts): string
    {
        $html = '<select id="trophee_chasse" name="trophee_chasse">';
        $html .= '<option value="0">' . esc_html__('Global', 'chassesautresor-com') . '</option>';
        foreach ($hunts as $hunt) {
            $id = (int) ($hunt['id'] ?? 0);
            $title = trim((string) ($hunt['title'] ?? ''));
            if ($id <= 0 || $title === '') {
                continue;
            }
            $html .= '<option value="' . $id . '">' . esc_html($title) . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    private function scopes(): string
    {
        $options = [
            'user' => __('Joueur', 'chassesautresor-com'),
            'team' => __('Équipe', 'chassesautresor-com'),
            'both' => __('Joueur et équipe', 'chassesautresor-com'),
        ];
        $html = '<select id="trophee_attribution" name="trophee_attribution">';
        foreach ($options as $value => $label) {
            $html .= '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    private function notice(string $notice): string
    {
        return match ($notice) {
            'image' => __('Image non acceptée.', 'chassesautresor-com'),
            'invalid' => __('Le titre et une clé d’événement valide sont requis.', 'chassesautresor-com'),
            'save' => __('Le trophée n’a pas pu être créé.', 'chassesautresor-com'),
            default => '',
        };
    }
}
