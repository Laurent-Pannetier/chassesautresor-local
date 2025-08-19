<?php
/**
 * Helper functions for "Mon Compte" area.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

/**
 * Retrieve organizer navigation data for the sidebar.
 *
 * @param int $user_id User ID.
 * @return array|null Navigation data or null if no organizer.
 */
function myaccount_get_organizer_nav(int $user_id): ?array
{
    $organizer_id = get_organisateur_from_user($user_id);
    if (!$organizer_id) {
        return null;
    }

    $organizer_post_status = get_post_status($organizer_id);
    $organizer_complete    = (bool) get_field('organisateur_cache_complet', $organizer_id);
    $organizer_classes     = 'dashboard-nav-link';

    if (!$organizer_complete) {
        $organizer_classes .= ' status-important';
    } elseif ($organizer_post_status === 'pending') {
        $organizer_classes .= ' status-pending';
    } else {
        $organizer_classes .= ' status-published';
    }

    $chasses = get_posts([
        'post_type'   => 'chasse',
        'post_status' => ['publish', 'pending'],
        'numberposts' => -1,
        'meta_query'  => [
            'relation' => 'AND',
            [
                'key'     => 'chasse_cache_organisateur',
                'value'   => '"' . $organizer_id . '"',
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'chasse_cache_statut_validation',
                'value'   => 'banni',
                'compare' => '!=',
            ],
        ],
    ]);

    $pending_enigmes = recuperer_enigmes_tentatives_en_attente($organizer_id);

    $data = [
        'organizer' => [
            'url'     => get_permalink($organizer_id),
            'title'   => get_the_title($organizer_id),
            'classes' => $organizer_classes,
        ],
        'chasses' => [],
    ];

    foreach ($chasses as $chasse) {
        $status_validation = get_field('chasse_cache_statut_validation', $chasse->ID);
        $complet           = get_field('chasse_cache_complet', $chasse->ID);
        $post_status       = get_post_status($chasse->ID);
        $classes           = 'dashboard-nav-sublink';
        $pending_icon      = false;

        if (!$complet) {
            $classes .= ' status-important';
        } else {
            if ($post_status === 'pending') {
                if ($status_validation === 'banni') {
                    continue;
                } elseif ($status_validation === 'en_attente') {
                    $classes     .= ' status-pending';
                    $pending_icon = true;
                } else {
                    $classes .= ' status-pending';
                }
            } elseif ($post_status === 'publish') {
                if ($status_validation === 'valide') {
                    $classes .= ' status-published';
                } else {
                    $classes .= ' status-pending';
                }
            } else {
                $classes .= ' status-pending';
            }
        }

        if (strpos($classes, 'status-published') === false) {
            $classes .= ' status-muted';
        }

        $chasse_item = [
            'title'        => get_the_title($chasse->ID),
            'url'          => get_permalink($chasse->ID),
            'classes'      => $classes,
            'pending_icon' => $pending_icon,
            'enigmes'      => [],
        ];

        $enigme_ids = recuperer_ids_enigmes_pour_chasse($chasse->ID);
        foreach ($enigme_ids as $enigme_id) {
            $sub_classes      = 'dashboard-nav-subitem';
            $url              = get_permalink($enigme_id);
            $enigme_complete  = get_field('enigme_cache_complet', $enigme_id);
            $post_status      = get_post_status($enigme_id);
            $etat_enigme      = get_field('enigme_cache_etat_systeme', $enigme_id);

            if (!$enigme_complete) {
                $sub_classes .= ' status-important';
            } else {
                if ($post_status === 'pending') {
                    if (in_array($etat_enigme, ['invalide', 'cache_invalide'], true)) {
                        continue;
                    }
                    $sub_classes .= ' status-pending';
                } elseif ($post_status === 'publish') {
                    if ($etat_enigme === 'accessible') {
                        $sub_classes .= ' status-published';
                    } elseif (in_array($etat_enigme, ['bloquee_date', 'bloquee_chasse', 'bloquee_pre_requis'], true)) {
                        $sub_classes .= ' status-pending';
                    } else {
                        $sub_classes .= ' status-pending';
                    }
                } else {
                    $sub_classes .= ' status-pending';
                }
            }

            if (in_array($enigme_id, $pending_enigmes, true)) {
                $sub_classes .= ' status-important';
            }

            if (strpos($classes, 'status-published') === false) {
                $sub_classes .= ' status-muted';
            }

            $chasse_item['enigmes'][] = [
                'title'   => get_the_title($enigme_id),
                'url'     => $url,
                'classes' => $sub_classes,
            ];
        }

        $data['chasses'][] = $chasse_item;
    }

    return $data;
}

/**
 * Render organizer navigation HTML for the sidebar.
 *
 * @param array $data Navigation data from myaccount_get_organizer_nav().
 * @return string HTML output.
 */
function myaccount_render_organizer_nav(array $data): string
{
    ob_start();
    ?>
    <nav class="dashboard-nav organizer-nav">
        <a href="<?php echo esc_url($data['organizer']['url']); ?>" class="<?php echo esc_attr($data['organizer']['classes']); ?>">
            <i class="fas fa-landmark"></i>
            <span class="nav-title"><?php echo esc_html($data['organizer']['title']); ?></span>
        </a>
        <?php foreach ($data['chasses'] as $chasse) : ?>
            <?php
            $tag  = $chasse['url'] ? 'a' : 'span';
            $attr = $chasse['url'] ? ' href="' . esc_url($chasse['url']) . '"' : '';
            ?>
            <<?php echo $tag . $attr; ?> class="<?php echo esc_attr($chasse['classes']); ?>">
                <span class="nav-title"><?php echo esc_html($chasse['title']); ?></span>
                <?php if ($chasse['pending_icon']) : ?>
                    <i class="fas fa-hourglass-half"></i>
                <?php endif; ?>
            </<?php echo $tag; ?>>
            <?php foreach ($chasse['enigmes'] as $enigme) : ?>
                <?php
                $sub_tag  = $enigme['url'] ? 'a' : 'span';
                $sub_attr = $enigme['url'] ? ' href="' . esc_url($enigme['url']) . '"' : '';
                ?>
                <<?php echo $sub_tag . $sub_attr; ?> class="<?php echo esc_attr($enigme['classes']); ?>">
                    <span class="nav-title"><?php echo esc_html($enigme['title']); ?></span>
                </<?php echo $sub_tag; ?>>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
    <?php
    return ob_get_clean();
}
