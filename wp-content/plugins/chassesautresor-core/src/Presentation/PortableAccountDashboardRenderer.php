<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Presentation;

use ChassesAuTresor\Core\Messages\AccountSectionAccessService;
use ChassesAuTresor\Core\Messages\AccountSectionRenderer;
use ChassesAuTresor\Core\Users\AccountOrdersRenderer;

/** Provide role-aware WooCommerce dashboard content when the historical theme is inactive. */
final class PortableAccountDashboardRenderer {
    public function render(): void {
        if (get_stylesheet() === 'chassesautresor' || !is_user_logged_in()) {
            return;
        }

        echo myaccount_get_important_messages(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $user = wp_get_current_user();
        if (in_array('administrator', (array) $user->roles, true)) {
            echo $this->administrator(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }
        if (array_intersect(['organisateur', 'organisateur_creation'], (array) $user->roles)) {
            echo $this->organizer((int) $user->ID); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    private function administrator(): string {
        $section = isset($_GET['section']) ? sanitize_key(wp_unslash($_GET['section'])) : '';
        if ($section !== '') {
            $decision = (new AccountSectionAccessService())->resolve(true, $section, true);
            if ($decision['error'] === null) {
                return '<div id="cat-core-account-section">'
                    . (new AccountSectionRenderer())->render($decision['template']) . '</div>';
            }
        }

        $items = [
            'organisateurs' => __('Organisateurs', 'chassesautresor-com'),
            'statistiques' => __('Statistiques', 'chassesautresor-com'),
            'outils' => __('Outils', 'chassesautresor-com'),
        ];
        $html = '<section class="cat-core-account-admin"><h2>'
            . esc_html__('Administration', 'chassesautresor-com') . '</h2><nav class="cat-core-account-nav">';
        foreach ($items as $key => $label) {
            $url = add_query_arg('section', $key, wc_get_account_endpoint_url('dashboard'));
            $html .= '<a class="cat-core-button" data-account-section="' . esc_attr($key) . '" href="'
                . esc_url($url) . '">' . esc_html($label) . '</a>';
        }

        return $html . '</nav><div id="cat-core-account-section" aria-live="polite"></div></section>';
    }

    private function organizer(int $userId): string {
        $organizerId = (int) get_organisateur_from_user($userId);
        $html = '<section class="cat-core-account-organizer"><h2>'
            . esc_html__('Espace organisateur', 'chassesautresor-com') . '</h2>';
        if ($organizerId > 0) {
            $html .= '<p><a class="cat-core-button" href="' . esc_url(get_permalink($organizerId)) . '">'
                . esc_html__('Gérer mon profil organisateur', 'chassesautresor-com') . '</a></p>';
            $hunts = get_chasses_de_organisateur($organizerId);
            $huntIds = is_object($hunts) && isset($hunts->posts) ? $hunts->posts : (array) $hunts;
            if ($huntIds !== []) {
                $html .= '<h3>' . esc_html__('Mes chasses', 'chassesautresor-com')
                    . '</h3><ul class="cat-core-cards">';
                foreach ($huntIds as $hunt) {
                    $huntId = is_object($hunt) ? (int) ($hunt->ID ?? 0) : (int) $hunt;
                    if ($huntId > 0) {
                        $html .= '<li><a href="' . esc_url(get_permalink($huntId)) . '">'
                            . esc_html(get_the_title($huntId)) . '</a></li>';
                    }
                }
                $html .= '</ul>';
            }
        }
        $orders = (new AccountOrdersRenderer())->render($userId, 3);
        if ($orders !== '') {
            $html .= '<h3>' . esc_html__('Commandes récentes', 'chassesautresor-com') . '</h3>' . $orders;
        }

        return $html . '</section>';
    }
}
