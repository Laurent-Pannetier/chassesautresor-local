<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\OrganizerRoleService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/**
 * Retrieve persistent account messages visible in the current content context.
 *
 * @return array<int, array<string, mixed>>
 */
function myaccount_get_persistent_messages(int $userId): array
{
    global $wpdb;

    $messages = CoreServiceFactory::accountMessages($wpdb)->getPersistent($userId);
    $currentId = get_queried_object_id();
    $currentType = get_post_type($currentId);
    $currentHunt = 0;

    if ($currentType === 'chasse') {
        $currentHunt = $currentId;
    } elseif ($currentType === 'enigme') {
        $currentHunt = (new RelationshipService())->normalizeId(
            get_field('enigme_chasse_associee', $currentId)
        ) ?? 0;
    }

    $attempts = [];
    foreach ($messages as $key => $message) {
        $item = is_array($message)
            ? $message
            : ['text' => $message, 'type' => 'info', 'dismissible' => false];
        if (strpos((string) $key, 'tentative_') !== 0) {
            $messages[$key] = $item;
            continue;
        }

        $text = (string) ($item['text'] ?? '');
        $attempts[] = preg_match('/<a[^>]*>.*?<\/a>/', $text, $matches) ? $matches[0] : $text;
        unset($messages[$key]);
    }

    $output = [];
    foreach ($messages as $key => $message) {
        if (!is_array($message) || !isset($message['text'])) {
            continue;
        }

        $scope = isset($message['chasse_scope']) ? (int) $message['chasse_scope'] : 0;
        if (
            ($scope > 0 && ($scope !== $currentHunt ||
                (empty($message['include_enigmes']) && $currentType === 'enigme')))
            || ($scope === 0 && $currentType === 'enigme')
        ) {
            continue;
        }

        $output[] = [
            'key' => (string) $key,
            'text' => (string) $message['text'],
            'message_key' => (string) ($message['message_key'] ?? ''),
            'locale' => (string) ($message['locale'] ?? ''),
            'type' => (string) ($message['type'] ?? 'info'),
            'dismissible' => !empty($message['dismissible']),
        ];
    }

    if (count($attempts) === 1) {
        $output[] = [
            'text' => sprintf(
                __('Votre demande de résolution de l\'énigme %s est en cours de traitement. Vous recevrez une '
                    . 'notification dès que votre demande sera traitée.', 'chassesautresor-com'),
                $attempts[0]
            ),
            'type' => 'info',
        ];
    } elseif (count($attempts) > 1) {
        $links = array_map(
            static fn (string $anchor): string => str_replace('<a ', '<a class="etiquette" ', $anchor),
            $attempts
        );
        $output[] = [
            'text' => sprintf(
                __('Vos demandes de résolution d\'énigmes sont en cours de traitement : %s. Vous recevrez une '
                    . 'notification dès que vos demandes seront traitées.', 'chassesautresor-com'),
                implode(' ', $links)
            ),
            'type' => 'info',
        ];
    }

    return $output;
}

/** @return array<int, array<string, mixed>> */
function myaccount_get_flash_messages(int $userId): array
{
    global $wpdb;

    $messages = [];
    foreach (CoreServiceFactory::accountMessages($wpdb)->pullFlash($userId) as $message) {
        if (isset($message['text'])) {
            $messages[] = [
                'text' => (string) $message['text'],
                'type' => (string) ($message['type'] ?? 'info'),
                'dismissible' => !empty($message['dismissible']),
            ];
        }
    }

    return $messages;
}

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
        $pending = get_posts([
            'post_type' => 'chasse',
            'post_status' => ['publish', 'pending', 'draft'],
            'numberposts' => -1,
            'fields' => 'ids',
            'meta_key' => 'chasse_cache_statut_validation',
            'meta_value' => 'en_attente',
        ]);

        if (!empty($pending)) {
            $links = array_map(
                function ($huntId) {
                    $url   = esc_url(get_permalink($huntId));
                    $title = esc_html(get_the_title($huntId));
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

        global $wpdb;
        $pendingRequests = CoreServiceFactory::conversion($wpdb)->getRequests(null, 'pending');

        if (!empty($pendingRequests)) {
            $messages[] = [
                'text' => __('Des demandes de conversion sont en attente de traitement.', 'chassesautresor-com'),
                'type' => 'info',
            ];
        }
    }

    $user = wp_get_current_user();
    $organizerRole = defined('ROLE_ORGANISATEUR') ? ROLE_ORGANISATEUR : 'organisateur';
    $creationRole = defined('ROLE_ORGANISATEUR_CREATION')
        ? ROLE_ORGANISATEUR_CREATION
        : 'organisateur_creation';
    $isOrganizer = (new OrganizerRoleService())->isOrganizer((array) $user->roles, $organizerRole, $creationRole);

    if ($isOrganizer) {
        $current_user_id   = get_current_user_id();
        global $wpdb;
        $organisateur_id = CoreServiceFactory::organizer($wpdb)->findIdForUser($current_user_id);

        $pendingOwn = CoreServiceFactory::conversion($wpdb)->getRequests($current_user_id, 'pending');
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
