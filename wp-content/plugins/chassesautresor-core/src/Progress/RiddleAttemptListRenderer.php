<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Render the paginated list of submissions for a riddle. */
final class RiddleAttemptListRenderer
{
    /** @param array<string, mixed> $arguments */
    public function render(array $arguments): string
    {
        $attempts = (array) ($arguments['tentatives'] ?? []);
        $page = (int) ($arguments['page'] ?? 1);
        $pages = (int) ($arguments['pages'] ?? 1);
        if ($attempts === []) {
            return '<p>' . esc_html__('Aucune tentative de soumission.', 'chassesautresor-com') . '</p>';
        }

        ob_start();
        ?>
        <table class="table-tentatives">
            <thead><tr>
                <th><?= esc_html__('Date', 'chassesautresor-com'); ?></th>
                <th><?= esc_html__('Utilisateur', 'chassesautresor-com'); ?></th>
                <th><?= esc_html__('Proposition', 'chassesautresor-com'); ?></th>
                <th><?= esc_html__('Résultat', 'chassesautresor-com'); ?></th>
            </tr></thead>
            <tbody><?php foreach ($attempts as $attempt) : ?>
                <?= $this->row($attempt); ?>
            <?php endforeach; ?></tbody>
        </table>
        <?= $this->pager($page, $pages); ?>
        <?php
        return (string) ob_get_clean();
    }

    private function row(object $attempt): string
    {
        $user = get_userdata((int) $attempt->user_id);
        $login = $user && isset($user->user_login)
            ? (string) $user->user_login
            : esc_html__('Inconnu', 'chassesautresor-com');
        $isPending = $attempt->resultat === 'attente' && (int) $attempt->traitee === 0;
        $uid = isset($attempt->tentative_uid) ? (string) $attempt->tentative_uid : '';
        $options = $uid !== '' ? \cta_prepare_masked_proposition_options($uid) : [];
        $proposition = \cta_render_proposition_cell(
            $uid !== '' ? '' : (string) ($attempt->reponse_saisie ?? ''),
            false,
            39,
            $options
        );
        $labels = [
            'bon' => esc_html__('bon', 'chassesautresor-com'),
            'faux' => esc_html__('faux', 'chassesautresor-com'),
            'attente' => esc_html__('attente', 'chassesautresor-com'),
        ];
        $classes = ['bon' => 'etiquette-success', 'attente' => 'etiquette-pending'];

        ob_start();
        ?>
        <tr class="<?= $isPending ? 'tentative-pending' : ''; ?>">
            <td><?= esc_html(mysql2date('d/m/y H:i', $attempt->date_tentative)); ?></td>
            <td><?= esc_html($login); ?></td>
            <?= $proposition; ?>
            <td><span class="etiquette <?= esc_attr($classes[$attempt->resultat] ?? 'etiquette-error'); ?>">
                <?= esc_html($labels[$attempt->resultat] ?? $attempt->resultat); ?>
            </span><?= $isPending ? $this->reviewForm($uid) : ''; ?></td>
        </tr>
        <?php
        return (string) ob_get_clean();
    }

    private function reviewForm(string $uid): string
    {
        ob_start();
        ?>
        <form method="post" style="display:inline;">
            <?php wp_nonce_field('traiter_tentative_' . $uid); ?>
            <input type="hidden" name="uid" value="<?= esc_attr($uid); ?>">
            <button type="submit" name="action_traitement" value="valider" class="bouton-cta">
                <?= esc_html__('Valider', 'chassesautresor-com'); ?>
            </button>
            <button type="submit" name="action_traitement" value="invalider" class="bouton-secondaire">
                <?= esc_html__('Invalider', 'chassesautresor-com'); ?>
            </button>
        </form>
        <?php
        return (string) ob_get_clean();
    }

    private function pager(int $page, int $pages): string
    {
        if ($pages <= 1) {
            return '';
        }
        $options = '';
        for ($index = 1; $index <= $pages; $index++) {
            $selected = $index === $page ? ' selected="selected"' : '';
            $options .= '<option value="' . esc_attr($index) . '"' . $selected . '>' . esc_html($index) . '</option>';
        }
        return '<nav class="pager enigme-tentatives-pager" data-current="' . esc_attr($page)
            . '" data-total="' . esc_attr($pages) . '"><button type="button" class="etiquette pager-first"'
            . ' aria-label="' . esc_attr__('First page', 'chassesautresor-com') . '">&laquo;</button>'
            . '<button type="button" class="etiquette pager-prev" aria-label="'
            . esc_attr__('Previous page', 'chassesautresor-com') . '">&lsaquo;</button>'
            . '<span class="pager-info"><select class="etiquette pager-select" aria-label="'
            . esc_attr__('Go to page', 'chassesautresor-com') . '">' . $options . '</select> / '
            . esc_html($pages) . '</span><button type="button" class="etiquette pager-next" aria-label="'
            . esc_attr__('Next page', 'chassesautresor-com') . '">&rsaquo;</button>'
            . '<button type="button" class="etiquette pager-last" aria-label="'
            . esc_attr__('Last page', 'chassesautresor-com') . '">&raquo;</button></nav>';
    }
}
