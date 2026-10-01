<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Render rows and pagination for the current user's attempts table. */
final class UserAttemptsRenderer
{
    /** @param array<string, mixed> $view */
    public function rows(array $view): string
    {
        $attempts = (array) ($view['tentatives'] ?? []);
        if ((int) ($view['filtered_total'] ?? 0) <= 0 || $attempts === []) {
            return '<tr class="tentatives-empty"><td colspan="5">'
                . esc_html((string) ($view['no_results_message'] ?? '')) . '</td></tr>';
        }

        ob_start();
        foreach ($attempts as $attempt) {
            echo $this->row($attempt);
        }

        return trim((string) ob_get_clean());
    }

    /** @param array<string, mixed> $view */
    public function pager(array $view): string
    {
        return \cta_render_pager(
            (int) $view['page'],
            (int) $view['pages'],
            'tentatives-pager',
            ['data-param' => 'tentatives-page', 'data-section' => '', 'data-search-key' => 'tentatives']
        );
    }

    private function row(object $attempt): string
    {
        $huntId = isset($attempt->chasse_id) ? (int) $attempt->chasse_id : 0;
        $huntTitle = isset($attempt->chasse_title) ? (string) $attempt->chasse_title : '';
        $uid = isset($attempt->tentative_uid) ? (string) $attempt->tentative_uid : '';
        $options = $uid !== '' ? \cta_prepare_masked_proposition_options($uid) : [];
        $result = (string) $attempt->resultat;
        $classes = ['bon' => 'etiquette-success', 'attente' => 'etiquette-pending'];
        ob_start();
        ?>
        <tr>
            <td><?= esc_html(mysql2date('d/m/Y H:i', $attempt->date_tentative)); ?></td>
            <td><?= $this->hunt($huntId, $huntTitle); ?></td>
            <td><?= esc_html($attempt->enigme_title ?? ''); ?></td>
            <?= \cta_render_proposition_cell(
                $uid !== '' ? '' : (string) ($attempt->reponse_saisie ?? ''),
                false,
                39,
                $options
            ); ?>
            <td><span class="etiquette <?= esc_attr($classes[$result] ?? 'etiquette-error'); ?>">
                <?= esc_html__($result, 'chassesautresor-com'); ?>
            </span></td>
        </tr>
        <?php
        return (string) ob_get_clean();
    }

    private function hunt(int $huntId, string $title): string
    {
        if ($huntId > 0 && $title !== '') {
            return '<a href="' . esc_url(get_permalink($huntId)) . '">' . esc_html($title) . '</a>';
        }

        return $title !== '' ? esc_html($title) : '&mdash;';
    }
}
