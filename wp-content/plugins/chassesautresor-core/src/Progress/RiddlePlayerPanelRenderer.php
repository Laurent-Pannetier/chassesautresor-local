<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Render the portable data-driven part of a riddle participation panel. */
final class RiddlePlayerPanelRenderer {
    private RiddleParticipationService $participation;
    private RiddleParticipationInfoService $information;
    private ?HuntProgressService $progress;

    public function __construct(
        ?RiddleParticipationService $participation = null,
        ?RiddleParticipationInfoService $information = null,
        ?HuntProgressService $progress = null
    ) {
        $this->participation = $participation ?? new RiddleParticipationService();
        $this->information = $information ?? new RiddleParticipationInfoService();
        $this->progress = $progress;
    }

    public function render(int $riddleId, int $userId, string $responseHtml = ''): string
    {
        $solved = est_enigme_resolue_par_utilisateur($userId, $riddleId);
        $content = $this->response($riddleId, $userId, $solved, $responseHtml);
        $hints = $this->participation->hints($riddleId, $userId);

        if ($hints['riddle'] !== [] || $hints['hunt'] !== []) {
            $content .= '<hr class="reponse-indices-separator" /><div class="zone-indices">';
            $content .= $this->hintGroup($hints['riddle'], __('Indices énigme', 'chassesautresor-com'));
            $content .= $this->hintGroup($hints['hunt'], __('Indices chasse', 'chassesautresor-com'));
            $content .= '<div class="indice-display"></div></div>';
        }

        $information = $this->information->build($riddleId, $userId, $solved);
        $content .= $this->information($information);
        if ($content === '') {
            return '';
        }

        return '<section class="participation">' . $this->header($information) . $content . '</section>';
    }

    private function response(int $riddleId, int $userId, bool $solved, string $responseHtml): string
    {
        if (!$solved) {
            return $responseHtml === '' ? '' : '<div class="zone-reponse">' . $responseHtml . '</div>';
        }

        $date = $this->progress()->getRiddleResolutionDate($userId, $riddleId);
        $message = $date
            ? sprintf(
                __('Vous avez résolu cette énigme le %s.', 'chassesautresor-com'),
                wp_date('d/m/y \\à H:i', strtotime($date))
            )
            : __('Énigme résolue', 'chassesautresor-com');

        return '<div class="zone-reponse"><p class="message-joueur-statut">✅ '
            . esc_html($message) . '</p></div>';
    }

    /** @param array<int,array<string,mixed>> $hints */
    private function hintGroup(array $hints, string $title): string
    {
        if ($hints === []) {
            return '';
        }

        $html = '<div class="zone-indices-line"><span class="zone-indices-line__label">'
            . esc_html($title) . '</span><div class="indice-list">';
        foreach ($hints as $hint) {
            $html .= $this->hint($hint);
        }

        return $html . '</div></div>';
    }

    /** @param array<string,mixed> $hint */
    private function hint(array $hint): string
    {
        if ($hint['state'] === 'programme') {
            $timestamp = $hint['available_at'];
            $now = current_time('timestamp');
            if ($timestamp === false || $timestamp > $now) {
                return '<span class="indice-label indice-link--upcoming etiquette">'
                    . '<i class="fa-solid fa-hourglass" aria-hidden="true"></i> '
                    . esc_html($this->availabilityLabel($timestamp, $now)) . '</span>';
            }
        }

        $unlocked = (bool) $hint['unlocked'];
        $cost = (int) $hint['cost'];
        $costHtml = $cost > 0
            ? ' - ' . $cost . ' <sup>' . esc_html__('pts', 'chassesautresor-com') . '</sup>'
            : '';

        return '<a href="#" class="indice-link indice-link--' . ($unlocked ? 'unlocked' : 'locked')
            . ' etiquette" data-indice-id="' . esc_attr($hint['id']) . '" data-cout="' . esc_attr($cost)
            . '" data-unlocked="' . ($unlocked ? '1' : '0') . '"><i class="fa-solid '
            . ($unlocked ? 'fa-eye' : 'fa-lightbulb') . '" aria-hidden="true"></i> '
            . esc_html($hint['title']) . $costHtml . '</a>';
    }

    /** @param int|false $timestamp */
    private function availabilityLabel($timestamp, int $now): string
    {
        if ($timestamp === false) {
            return __('Bientôt disponible', 'chassesautresor-com');
        }
        if (wp_date('Y-m-d', $timestamp) === wp_date('Y-m-d', $now)) {
            return sprintf(__('Aujourd’hui à %s', 'chassesautresor-com'), wp_date('H:i', $timestamp));
        }

        return wp_date($timestamp <= $now + WEEK_IN_SECONDS ? 'd/m/y \\à H:i' : 'd/m/y', $timestamp);
    }

    /** @param array<string,int|string|bool> $information */
    private function information(array $information): string
    {
        if (!$information['show_info']) {
            return '';
        }

        $html = '<div class="participation-infos txt-small" '
            . 'style="color:var(--color-text-primary);display:flex;justify-content:space-between;">';
        $html .= (int) $information['cost'] > 0
            ? '<span class="solde">' . sprintf(
                esc_html__('Solde : %d pts', 'chassesautresor-com'),
                (int) $information['balance']
            ) . '</span>'
            : '<span></span>';

        if ((int) $information['cost'] > 0) {
            $html .= '<span></span>';
        }

        return $html . '</div>';
    }

    /** @param array<string,int|string|bool> $information */
    private function header(array $information): string
    {
        $badge = '';
        $cost = (int) $information['cost'];
        if ($information['validation_mode'] !== 'aucune' && $cost > 0) {
            $badge = '<span class="badge-cout" aria-label="'
                . esc_attr(sprintf(__('Coût par tentative : %d points.', 'chassesautresor-com'), $cost))
                . '">' . esc_html($cost) . ' ' . esc_html__('pts', 'chassesautresor-com') . '</span>';
        }

        return '<div class="participation-header"><span></span>' . $badge . '</div>';
    }

    private function progress(): HuntProgressService
    {
        if ($this->progress === null) {
            global $wpdb;
            $this->progress = CoreServiceFactory::huntProgress($wpdb);
        }

        return $this->progress;
    }
}
