<?php

declare(strict_types=1);

/**
 * Get pre-formatted HTML for the important message section in My Account pages.
 *
 * @return string
 */
function myaccount_get_important_messages(): string {
    $current_user_id = get_current_user_id();
    $messages = array_merge(
        myaccount_get_persistent_messages($current_user_id),
        myaccount_get_flash_messages($current_user_id)
    );
    $flash = '';

    if (isset($_GET['points_modifies']) && $_GET['points_modifies'] === '1') {
        $flash = '<p class="flash flash--success">'
            . __('Points mis à jour avec succès.', 'chassesautresor-com')
            . '</p>';
    }

    if (current_user_can('administrator')) {
        if (function_exists('recuperer_organisateurs_pending')) {
            $pending = array_filter(
                recuperer_organisateurs_pending(),
                function ($entry) {
                    return !empty($entry['chasse_id']) && $entry['validation'] === 'en_attente';
                }
            );

            if (!empty($pending)) {
                $links = array_map(
                    function ($entry) {
                        $url   = esc_url(get_permalink($entry['chasse_id']));
                        $title = esc_html(get_the_title($entry['chasse_id']));
                        return '<a href="' . $url . '">' . $title . '</a>';
                    },
                    $pending
                );

                $label = count($pending) > 1
                    ? __('Chasses à valider :', 'chassesautresor-com')
                    : __('Chasse à valider :', 'chassesautresor-com');

                $messages[] = [
                    'text' => $label . ' ' . implode(', ', $links),
                    'type' => 'info',
                ];
            }
        }

        $pendingRequests = cat_get_conversion_service()->getRequests(null, 'pending');

        if (!empty($pendingRequests)) {
            $messages[] = [
                'text' => __('Des demandes de conversion sont en attente de traitement.', 'chassesautresor-com'),
                'type' => 'info',
            ];
        }
    }

    if (est_organisateur()) {
        $current_user_id   = get_current_user_id();
        $organisateur_id   = get_organisateur_from_user($current_user_id);

        $pendingOwn = cat_get_conversion_service()->getRequests($current_user_id, 'pending');
        if (!empty($pendingOwn)) {
            $conversion_url = $organisateur_id
                ? esc_url(
                    add_query_arg(
                        [
                            'edition' => 'open',
                            'onglet'  => 'revenus',
                        ],
                        get_permalink($organisateur_id)
                    )
                )
                : esc_url(home_url('/mon-compte/'));

            $messages[] = [
                'text' => sprintf(
                    /* translators: 1: opening anchor tag, 2: closing anchor tag */
                    __('Vous avez une %1$sdemande de conversion%2$s en attente de règlement.', 'chassesautresor-com'),
                    '<a href="' . $conversion_url . '">',
                    '</a>'
                ),
                'type' => 'info',
            ];
        }

        if ($organisateur_id) {
            $pendingChasses = get_posts([
                'post_type'   => 'chasse',
                'post_status' => ['publish', 'pending'],
                'numberposts' => -1,
                'fields'      => 'ids',
                'meta_query'  => [
                    [
                        'key'     => 'chasse_cache_organisateur',
                        'value'   => '"' . $organisateur_id . '"',
                        'compare' => 'LIKE',
                    ],
                    [
                        'key'   => 'chasse_cache_statut_validation',
                        'value' => 'en_attente',
                    ],
                ],
            ]);

            if (!empty($pendingChasses)) {
                foreach ($pendingChasses as $chasse_id) {
                    $url   = esc_url(get_permalink($chasse_id));
                    $title = esc_html(get_the_title($chasse_id));
                    $messages[] = [
                        'text' => sprintf(
                            /* translators: %s: hunt title with link */
                            __('Demande pour %s en cours de traitement', 'chassesautresor-com'),
                            '<a href="' . $url . '">' . $title . '</a>'
                        ),
                        'type' => 'info',
                    ];
                }
            }
        }
    }

    if (empty($messages) && $flash === '') {
        return '';
    }

    $output = array_map(
        function ($msg) {
            $type        = $msg['type'] ?? 'info';
            $text        = $msg['text'] ?? '';
            if (!empty($msg['message_key'])) {
                if (!empty($msg['locale']) && function_exists('switch_to_locale')) {
                    switch_to_locale($msg['locale']);
                    $text = __($msg['message_key'], 'chassesautresor-com');
                    restore_previous_locale();
                } else {
                    $text = __($msg['message_key'], 'chassesautresor-com');
                }
            }
            $dismissible = !empty($msg['dismissible']) && !empty($msg['key']);

            switch ($type) {
                case 'success':
                    $class = 'message-succes';
                    $aria  = 'role="status" aria-live="polite"';
                    break;
                case 'error':
                    $class = 'message-erreur';
                    $aria  = 'role="alert" aria-live="assertive"';
                    break;
                case 'warning':
                    $class = 'message-info';
                    $aria  = 'role="status" aria-live="polite"';
                    break;
                default:
                    $class = 'message-info';
                    $aria  = 'role="status" aria-live="polite"';
                    break;
            }

            $button = '';
            if ($dismissible) {
                $button = ' <button type="button" class="message-close" data-key="'
                    . esc_attr($msg['key'])
                    . '" aria-label="'
                    . esc_attr__('Supprimer ce message', 'chassesautresor-com')
                    . '">×</button>';
            }

            return '<p class="' . esc_attr($class) . '" ' . $aria . '>' . $text . $button . '</p>';
        },
        $messages
    );

    return $flash . implode('', $output);
}
