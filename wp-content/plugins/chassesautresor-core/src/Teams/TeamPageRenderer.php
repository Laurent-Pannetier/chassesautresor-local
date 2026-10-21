<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Teams;

/** HTML of the Équipe page. Machine codes from the service become sentences here. */
final class TeamPageRenderer
{
    /**
     * @param array<string, mixed> $view
     * @param callable(int): string $imageUrl
     */
    public function render(
        array $view,
        string $notice,
        string $actionUrl,
        callable $imageUrl,
        string $icon
    ): string {
        $this->actionUrl = $actionUrl;
        $this->imageUrl = $imageUrl;
        $this->icon = str_contains($icon, '<svg') ? $icon : '';
        $team = $view['team'] ?? null;
        $ownId = is_array($team) ? (int) ($team['id'] ?? 0) : 0;

        ob_start();
        $this->notice($notice);
        if (!is_array($team)) {
            $this->entryForms((int) ($view['max'] ?? TeamSettings::DEFAULT));
        } else {
            $this->personalCard($team);
        }
        if (!empty($view['manager'])) {
            $directory = is_array($view['directory'] ?? null) ? $view['directory'] : [];
            $this->management($directory, (int) ($view['max'] ?? TeamSettings::DEFAULT), $ownId);
        }

        return trim((string) ob_get_clean());
    }

    private string $actionUrl = '';

    /** @var callable(int): string */
    private $imageUrl;

    private string $icon = '';

    private function notice(string $code): void
    {
        $messages = [
            'created' => [true, __('Équipe créée.', 'chassesautresor-com')],
            'joined' => [true, __('Vous avez rejoint l’équipe.', 'chassesautresor-com')],
            'left' => [true, __('Vous avez quitté l’équipe.', 'chassesautresor-com')],
            'excluded' => [true, __('Joueur exclu de l’équipe.', 'chassesautresor-com')],
            'updated' => [true, __('Équipe mise à jour.', 'chassesautresor-com')],
            'regenerated' => [true, __('Nouveau code d’invitation.', 'chassesautresor-com')],
            'max_updated' => [true, __('Taille maximale enregistrée.', 'chassesautresor-com')],
            'invalid_name' => [false, __(
                'Le nom doit contenir entre 2 et 40 caractères : lettres, chiffres, espaces, apostrophes ou tirets.',
                'chassesautresor-com'
            )],
            'name_taken' => [false, __('Ce nom est déjà utilisé.', 'chassesautresor-com')],
            'already_member' => [false, __(
                'Vous faites déjà partie d’une équipe. Quittez-la pour en changer.',
                'chassesautresor-com'
            )],
            'invalid_code' => [false, __('Code d’invitation invalide.', 'chassesautresor-com')],
            'full' => [false, __('Cette équipe est complète.', 'chassesautresor-com')],
            'blocked' => [false, __('Vous avez été exclu de cette équipe.', 'chassesautresor-com')],
            'not_member' => [false, __('Ce joueur n’est pas membre de l’équipe.', 'chassesautresor-com')],
            'forbidden' => [false, __('Action impossible.', 'chassesautresor-com')],
            'busy' => [false, __('Réessayez dans un instant.', 'chassesautresor-com')],
            'invalid_image' => [false, __(
                'Image refusée. Formats acceptés : JPG, PNG, GIF, WEBP, 2 Mo maximum.',
                'chassesautresor-com'
            )],
            'invalid_max' => [false, __('La taille maximale doit être comprise entre 1 et 20.', 'chassesautresor-com')],
            'rate_limited' => [false, __(
                'Trop de tentatives. Réessayez dans quelques minutes.',
                'chassesautresor-com'
            )],
        ];
        if (!isset($messages[$code])) {
            return;
        }

        [$ok, $text] = $messages[$code];
        $class = $ok ? 'equipe-notice' : 'equipe-notice equipe-notice--error';
        echo '<p class="' . esc_attr($class) . '" role="' . ($ok ? 'status' : 'alert') . '">';
        echo esc_html($text);
        echo '</p>';
    }

    private function entryForms(int $max): void
    {
        echo '<div class="equipe-grid equipe-grid--split">';
        echo '<section class="equipe-card">';
        echo '<h2>' . esc_html__('Créer une équipe', 'chassesautresor-com') . '</h2>';
        echo '<p class="equipe-note">';
        echo esc_html(sprintf(
            __(
                'Une équipe compte au maximum %d joueurs. Vous en faites partie dès sa création.',
                'chassesautresor-com'
            ),
            $max
        ));
        echo '</p>';
        $this->openForm(true);
        $this->hidden('cat_equipe_action', 'create');
        $this->field(
            'equipe_nom',
            __('Nom', 'chassesautresor-com'),
            'text',
            ['required' => 'required', 'maxlength' => '40']
        );
        $this->fileField('create');
        $this->submit(__('Créer', 'chassesautresor-com'), 'bouton-secondaire');
        echo '</form></section>';

        echo '<section class="equipe-card">';
        echo '<h2>' . esc_html__('Rejoindre une équipe', 'chassesautresor-com') . '</h2>';
        echo '<p class="equipe-note">';
        echo esc_html__(
            'Saisissez le code communiqué par un membre de l’équipe.',
            'chassesautresor-com'
        );
        echo '</p>';
        $this->openForm(false);
        $this->hidden('cat_equipe_action', 'join');
        $this->field(
            'equipe_code',
            __('Code d’invitation', 'chassesautresor-com'),
            'text',
            [
                'required' => 'required',
                'maxlength' => '12',
                'autocapitalize' => 'characters',
                'spellcheck' => 'false',
                'autocomplete' => 'off',
            ]
        );
        $this->submit(__('Rejoindre', 'chassesautresor-com'), 'bouton-secondaire');
        echo '</form></section></div>';
    }

    /** @param array<string, mixed> $team */
    private function personalCard(array $team): void
    {
        echo '<section class="equipe-card equipe-hero">';
        $this->portrait($team);
        echo '<div class="equipe-hero__body">';
        echo '<h2>' . esc_html((string) $team['name']) . '</h2>';
        $this->countLine($team);
        $this->memberList($team, false);
        $this->codeLine($team);
        if (!empty($team['canEdit'])) {
            $this->editForm($team);
            $this->regenerateForm((int) $team['id']);
        }
        $this->openForm(false, __('Quitter cette équipe ?', 'chassesautresor-com'));
        $this->hidden('cat_equipe_action', 'leave');
        echo '<button type="submit" class="btn-danger">';
        echo esc_html__('Quitter', 'chassesautresor-com');
        echo '</button></form>';
        echo '</div></section>';
    }

    /**
     * @param array<int, array<string, mixed>> $directory
     */
    private function management(array $directory, int $max, int $ownId): void
    {
        echo '<section class="equipe-manage">';
        echo '<h2>' . esc_html__('Gestion des équipes', 'chassesautresor-com') . '</h2>';
        echo '<p class="equipe-note">';
        echo esc_html__(
            'Baisser le maximum n’exclut personne. Une équipe déjà au-dessus n’accepte plus de joueur.',
            'chassesautresor-com'
        );
        echo '</p>';
        $this->openForm(false);
        $this->hidden('cat_equipe_action', 'set_max');
        echo '<label class="equipe-field" for="equipe-max">';
        echo esc_html__('Joueurs par équipe', 'chassesautresor-com');
        echo '</label>';
        echo '<input id="equipe-max" name="equipe_max" type="number" required min="'
            . (string) TeamSettings::MIN . '" max="' . (string) TeamSettings::CAP . '" value="'
            . esc_attr((string) $max) . '">';
        $this->submit(__('Enregistrer', 'chassesautresor-com'), 'bouton-secondaire');
        echo '</form>';

        if ($directory === []) {
            echo '<p class="equipe-note">' . esc_html__('Aucune équipe active.', 'chassesautresor-com') . '</p>';
            echo '</section>';

            return;
        }

        echo '<div class="equipe-grid">';
        foreach ($directory as $team) {
            if (!is_array($team)) {
                continue;
            }
            $this->directoryCard($team, (int) ($team['id'] ?? 0) !== $ownId);
        }
        echo '</div></section>';
    }

    /** @param array<string, mixed> $team */
    private function directoryCard(array $team, bool $showEdit): void
    {
        echo '<section class="equipe-card">';
        $this->portrait($team);
        echo '<h3>' . esc_html((string) $team['name']) . '</h3>';
        $this->countLine($team);
        $this->memberList($team, true);
        $this->codeLine($team);
        if ($showEdit && !empty($team['canEdit'])) {
            $this->editForm($team);
            $this->regenerateForm((int) $team['id']);
        }
        echo '</section>';
    }

    /** @param array<string, mixed> $team */
    private function portrait(array $team): void
    {
        $imageId = (int) ($team['imageId'] ?? 0);
        $url = $imageId > 0 ? (string) ($this->imageUrl)($imageId) : '';
        if ($url !== '') {
            echo '<img class="equipe-portrait" src="' . esc_url($url) . '" alt="">';

            return;
        }

        echo '<span class="equipe-portrait equipe-portrait--empty" aria-hidden="true">';
        echo $this->icon;
        echo '</span>';
    }

    /** @param array<string, mixed> $team */
    private function countLine(array $team): void
    {
        echo '<p class="equipe-count">';
        echo esc_html(sprintf(
            __('%1$d / %2$d joueurs', 'chassesautresor-com'),
            (int) ($team['count'] ?? 0),
            (int) ($team['max'] ?? 0)
        ));
        echo '</p>';
    }

    /** @param array<string, mixed> $team */
    private function memberList(array $team, bool $management): void
    {
        $members = is_array($team['members'] ?? null) ? $team['members'] : [];
        echo '<ul class="equipe-members">';
        foreach ($members as $member) {
            if (!is_array($member)) {
                continue;
            }
            $userId = (int) ($member['id'] ?? 0);
            $name = trim((string) ($member['name'] ?? ''));
            if ($name === '') {
                $name = sprintf(__('Joueur %d', 'chassesautresor-com'), $userId);
            }
            echo '<li class="equipe-member"><span>' . esc_html($name);
            if (!empty($member['creator'])) {
                echo ' <span class="equipe-role">' . esc_html__('Créateur', 'chassesautresor-com') . '</span>';
            }
            echo '</span>';
            if ($management && !empty($member['canExclude'])) {
                $this->excludeForm((int) $team['id'], $userId);
            }
            echo '</li>';
        }
        echo '</ul>';
    }

    /** @param array<string, mixed> $team */
    private function codeLine(array $team): void
    {
        $code = (string) ($team['code'] ?? '');
        echo '<div class="equipe-code">';
        echo '<span>' . esc_html__('Code d’invitation', 'chassesautresor-com') . '</span>';
        echo '<strong>' . esc_html($code) . '</strong>';
        echo '<button type="button" class="bouton-tertiaire" data-equipe-copy="' . esc_attr($code) . '"';
        echo ' data-copied-label="' . esc_attr__('Copié', 'chassesautresor-com') . '">';
        echo esc_html__('Copier', 'chassesautresor-com');
        echo '</button>';
        echo '</div>';
    }

    /** @param array<string, mixed> $team */
    private function editForm(array $team): void
    {
        $this->openForm(true);
        $this->hidden('cat_equipe_action', 'update');
        $this->hidden('equipe_id', (string) (int) $team['id']);
        $this->field(
            'equipe_nom_' . (int) $team['id'],
            __('Nom', 'chassesautresor-com'),
            'text',
            ['required' => 'required', 'maxlength' => '40', 'value' => (string) $team['name']]
        );
        $this->fileField('equipe-' . (int) $team['id']);
        echo '<label class="equipe-check">';
        echo '<input type="checkbox" name="equipe_remove_image" value="1"> ';
        echo esc_html__('Retirer l’image', 'chassesautresor-com');
        echo '</label>';
        $this->submit(__('Enregistrer', 'chassesautresor-com'), 'bouton-secondaire');
        echo '</form>';
    }

    private function regenerateForm(int $teamId): void
    {
        $this->openForm(false);
        $this->hidden('cat_equipe_action', 'regenerate');
        $this->hidden('equipe_id', (string) $teamId);
        $this->submit(__('Régénérer le code', 'chassesautresor-com'), 'bouton-tertiaire');
        echo '</form>';
    }

    private function excludeForm(int $teamId, int $userId): void
    {
        $this->openForm(false, __('Exclure ce joueur de l’équipe ?', 'chassesautresor-com'));
        $this->hidden('cat_equipe_action', 'exclude');
        $this->hidden('equipe_id', (string) $teamId);
        $this->hidden('user_id', (string) $userId);
        echo '<button type="submit" class="btn-danger">';
        echo esc_html__('Exclure', 'chassesautresor-com');
        echo '</button></form>';
    }

    private function openForm(bool $upload, string $confirm = ''): void
    {
        echo '<form class="equipe-form" method="post" action="' . esc_url($this->actionUrl) . '"';
        if ($upload) {
            echo ' enctype="multipart/form-data"';
        }
        if ($confirm !== '') {
            echo ' data-equipe-confirm="' . esc_attr($confirm) . '"';
        }
        echo '>';
        if (function_exists('wp_nonce_field')) {
            wp_nonce_field('cat_equipe', 'cat_equipe_nonce');
        }
    }

    private function hidden(string $name, string $value): void
    {
        echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
    }

    /** @param array<string, string> $attributes */
    private function field(string $id, string $label, string $type, array $attributes): void
    {
        $name = str_starts_with($id, 'equipe_nom') ? 'equipe_nom' : $id;
        echo '<label class="equipe-field" for="' . esc_attr($id) . '">' . esc_html($label) . '</label>';
        echo '<input id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" type="' . esc_attr($type) . '"';
        foreach ($attributes as $key => $value) {
            echo ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        echo '>';
    }

    private function fileField(string $suffix): void
    {
        $id = 'equipe-image-' . $suffix;
        echo '<label class="equipe-field" for="' . esc_attr($id) . '">';
        echo esc_html__('Image (facultative)', 'chassesautresor-com');
        echo '</label>';
        echo '<input id="' . esc_attr($id) . '" name="equipe_image" type="file"';
        echo ' accept="image/jpeg,image/png,image/gif,image/webp">';
    }

    private function submit(string $label, string $class): void
    {
        echo '<button type="submit" class="' . esc_attr($class) . '">' . esc_html($label) . '</button>';
    }
}
