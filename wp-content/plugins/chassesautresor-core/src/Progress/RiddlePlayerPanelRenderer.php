<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Render the portable data-driven part of a riddle participation panel. */
final class RiddlePlayerPanelRenderer {
    /** Imposed hint placement: a cluster beside Répondre. Image anchors come later. */
    private const HINT_DISPLAY_GLOBAL = 'global';
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
        $response = $this->response($riddleId, $userId, $solved, $responseHtml);
        $hints = $this->participation->hints($riddleId, $userId);
        $hintHtml = '';

        if ($hints['riddle'] !== [] || $hints['hunt'] !== []) {
            $hintHtml = '<div class="zone-indices" data-hint-display="'
                . esc_attr(self::HINT_DISPLAY_GLOBAL) . '">';
            $hintHtml .= $this->hintGroup($hints['riddle'], __('Indices énigme', 'chassesautresor-com'));
            $hintHtml .= $this->hintGroup($hints['hunt'], __('Indices chasse', 'chassesautresor-com'));
            $hintHtml .= '<div class="indice-display"></div></div>';
        }

        $content = ($response !== '' || $hintHtml !== '')
            ? '<div class="participation-dock">' . $response . $hintHtml . '</div>'
            : '';

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
            $now = (int) current_time('timestamp');
            if ($timestamp === false || $timestamp > $now) {
                return $this->staticHint(
                    (string) $hint['title'],
                    $this->availabilityLabel($timestamp, $now),
                    'fa-hourglass',
                    'upcoming'
                );
            }
        }

        $gate = is_array($hint['gate'] ?? null) ? $hint['gate'] : ['open' => true];
        if ($gate['open'] === false && empty($hint['unlocked'])) {
            return $this->closedHint($hint, $gate);
        }

        $unlocked = (bool) $hint['unlocked'];
        $cost = (int) $hint['cost'];
        $costHtml = $cost > 0
            ? ' - ' . $cost . ' <sup>' . esc_html__('pts', 'chassesautresor-com') . '</sup>'
            : '';
        $stateClass = $unlocked ? 'unlocked indice-link--consulted' : 'locked';
        $icon = $unlocked ? 'fa-check' : 'fa-lightbulb';
        $status = $unlocked
            ? '<span class="indice-link__status">' . esc_html__('Consulté', 'chassesautresor-com') . '</span>'
            : '';
        $aria = $unlocked
            ? ' aria-label="' . esc_attr(sprintf(
                __('%s, consulté', 'chassesautresor-com'),
                (string) $hint['title']
            )) . '"'
            : '';

        return '<a href="#" class="indice-link indice-link--' . $stateClass
            . ' etiquette" data-indice-id="' . esc_attr($hint['id']) . '" data-cout="' . esc_attr($cost)
            . '" data-unlocked="' . ($unlocked ? '1' : '0') . '"' . $aria . '><i class="fa-solid '
            . $icon . '" aria-hidden="true"></i> '
            . '<span class="indice-link__text"><span class="indice-link__label">' . esc_html($hint['title'])
            . '</span>' . $status . '</span>'
            . $costHtml . '</a>';
    }

    /** @param array<string,mixed> $hint @param array<string,mixed> $gate */
    private function closedHint(array $hint, array $gate): string
    {
        if (($gate['reason'] ?? '') === 'prerequisite') {
            $missing = array_values(array_filter(
                array_map('strval', (array) ($gate['missing_labels'] ?? [])),
                static fn (string $label): bool => $label !== ''
            ));
            $status = $missing === []
                ? __('Prérequis manquant', 'chassesautresor-com')
                : sprintf(__('Après %s', 'chassesautresor-com'), implode(', ', $missing));

            return $this->staticHint((string) $hint['title'], $status, 'fa-lock', 'prerequisite');
        }

        $availableAt = $gate['available_at'] ?? null;
        if (is_int($availableAt)) {
            $status = $this->availabilityLabel($availableAt, (int) current_time('timestamp'));
        } elseif (($gate['target'] ?? '') === 'chasse') {
            $status = __('Après le début de la chasse', 'chassesautresor-com');
        } else {
            $status = __('Après le début de l’énigme', 'chassesautresor-com');
        }

        return $this->staticHint((string) $hint['title'], $status, 'fa-hourglass', 'upcoming');
    }

    private function staticHint(string $title, string $status, string $icon, string $modifier): string
    {
        return '<span class="indice-label indice-link--' . esc_attr($modifier) . ' etiquette" title="'
            . esc_attr($status) . '" aria-disabled="true" aria-label="'
            . esc_attr(sprintf(__('%1$s, %2$s', 'chassesautresor-com'), $title, $status)) . '">'
            . '<i class="fa-solid ' . esc_attr($icon) . '" aria-hidden="true"></i> '
            . '<span class="indice-link__text"><span class="indice-link__label">' . esc_html($title) . '</span>'
            . '<span class="indice-link__status">' . esc_html($status) . '</span></span></span>';
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

        $html = '<div class="participation-infos txt-small">';
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

        $className = 'participation-header' . ($badge === '' ? ' participation-header--empty' : '');

        return '<div class="' . $className . '"><span></span>' . $badge . '</div>';
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
