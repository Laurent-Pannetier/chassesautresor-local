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

    $data = [
        'organizer' => [
            'url'   => get_permalink($organizer_id),
            'title' => get_the_title($organizer_id),
        ],
        'chasses' => [],
    ];

    foreach ($chasses as $chasse) {
        $status_validation = get_field('chasse_cache_statut_validation', $chasse->ID);
        $complet           = get_field('chasse_cache_complet', $chasse->ID);
        $classes           = 'dashboard-nav-sublink';

        if (!$complet || in_array($status_validation, ['creation', 'correction'], true)) {
            $classes .= ' status-important';
        } elseif ($status_validation === 'banni') {
            $classes .= ' status-banned';
        } elseif ($status_validation === 'en_attente') {
            $classes .= ' status-pending';
        } else {
            $classes .= ' status-normal';
        }

        $chasse_item = [
            'title'        => get_the_title($chasse->ID),
            'url'          => $status_validation === 'banni' ? null : get_permalink($chasse->ID),
            'classes'      => $classes,
            'pending_icon' => $status_validation === 'en_attente',
            'enigmes'      => [],
        ];

        $enigme_ids = recuperer_ids_enigmes_pour_chasse($chasse->ID);
        foreach ($enigme_ids as $enigme_id) {
            $sub_classes = 'dashboard-nav-subitem ' . $classes;
            $url         = get_permalink($enigme_id);

            if (strpos($classes, 'status-normal') !== false) {
                $etat_enigme = get_field('enigme_cache_etat_systeme', $enigme_id);
                if (in_array($etat_enigme, ['bloquee_date', 'bloquee_pre_requis'], true)) {
                    $sub_classes .= ' status-muted';
                } elseif (in_array($etat_enigme, ['cache_invalide', 'invalide'], true)) {
                    $sub_classes .= ' status-banned';
                    $url         = null;
                }
            } else {
                if (strpos($classes, 'status-banned') !== false) {
                    $url = null;
                }
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
        <a href="<?php echo esc_url($data['organizer']['url']); ?>" class="dashboard-nav-link">
            <i class="fas fa-landmark"></i>
            <span><?php echo esc_html($data['organizer']['title']); ?></span>
        </a>
        <?php foreach ($data['chasses'] as $chasse) : ?>
            <?php
            $tag  = $chasse['url'] ? 'a' : 'span';
            $attr = $chasse['url'] ? ' href="' . esc_url($chasse['url']) . '"' : '';
            ?>
            <<?php echo $tag . $attr; ?> class="<?php echo esc_attr($chasse['classes']); ?>">
                <?php echo esc_html($chasse['title']); ?>
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
                    <?php echo esc_html($enigme['title']); ?>
                </<?php echo $sub_tag; ?>>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
    <?php
    return ob_get_clean();
}
