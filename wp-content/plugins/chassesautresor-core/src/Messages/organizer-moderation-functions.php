<?php

declare(strict_types=1);

const ORGANISATEURS_PENDING_PER_PAGE = 20;

/**
 * Récupère les organisateurs avec statut pending.
 *
 * @return array[] Liste des données des organisateurs en attente.
 */
function recuperer_organisateurs_pending(): array {
    if (!current_user_can('administrator')) {
        return [];
    }

    $query = new WP_Query([
        'post_type'      => 'organisateur',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'fields'         => 'ids',
    ]);

    $resultats = [];

    foreach ($query->posts as $organisateur_id) {
        $titre     = get_the_title($organisateur_id);
        $permalink = get_permalink($organisateur_id);

        $users     = (array) get_field('utilisateurs_associes', $organisateur_id);
        $user_id   = $users ? intval(reset($users)) : null;
        $user_name = '';
        $user_link = '';
        if ($user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                $user_name = $user->display_name;
                $user_link = get_edit_user_link($user_id);
            }
        }

        verifier_ou_mettre_a_jour_cache_complet($organisateur_id);

        $chasses = new WP_Query([
            'post_type'      => 'chasse',
            'posts_per_page' => -1,
            'post_status'    => ['publish', 'pending', 'draft'],
            'meta_query'     => [
                [
                    'key'     => 'chasse_cache_organisateur',
                    'value'   => '"' . strval($organisateur_id) . '"',
                    'compare' => 'LIKE',
                ],
            ],
            'fields'         => 'ids',
        ]);

        if ($chasses->have_posts()) {
            foreach ($chasses->posts as $chasse_id) {
                verifier_ou_mettre_a_jour_cache_complet($chasse_id);

                $date_creation = get_post_field('post_date', $chasse_id);
                $chasse_titre  = get_the_title($chasse_id);
                $chasse_link   = get_permalink($chasse_id);
                $enigmes       = recuperer_enigmes_associees($chasse_id);
                $nb_enigmes    = count($enigmes);
                $statut        = get_field('chasse_cache_statut_validation', $chasse_id);

                $pending_validation = ($statut === 'en_attente');
                $pending_attempts   = false;
                foreach ($enigmes as $enigme_id) {
                    $mode = enigme_normaliser_mode_validation(get_field('enigme_mode_validation', $enigme_id));
                    if ($mode === 'manuelle' && compter_tentatives_en_attente($enigme_id) > 0) {
                        $pending_attempts = true;
                        break;
                    }
                }

                $resultats[] = [
                    'organisateur_id'        => $organisateur_id,
                    'organisateur_titre'     => $titre,
                    'organisateur_permalink' => $permalink,
                    'user_id'                => $user_id,
                    'user_name'              => $user_name,
                    'user_link'              => $user_link,
                    'chasse_id'              => $chasse_id,
                    'chasse_titre'           => $chasse_titre,
                    'chasse_permalink'       => $chasse_link,
                    'nb_enigmes'             => $nb_enigmes,
                    'statut'                 => $statut,
                    'validation'             => $statut,
                    'pending_validation'     => $pending_validation,
                    'pending_attempts'       => $pending_attempts,
                    'date_creation'          => $date_creation,
                ];
            }
        } else {
            $date_creation = get_post_field('post_date', $organisateur_id);
            $resultats[]   = [
                'organisateur_id'        => $organisateur_id,
                'organisateur_titre'     => $titre,
                'organisateur_permalink' => $permalink,
                'user_id'                => $user_id,
                'user_name'              => $user_name,
                'user_link'              => $user_link,
                'chasse_id'              => null,
                'chasse_titre'           => '',
                'chasse_permalink'       => '',
                'nb_enigmes'             => 0,
                'statut'                 => '',
                'validation'             => '',
                'pending_validation'     => false,
                'pending_attempts'       => false,
                'date_creation'          => $date_creation,
            ];
        }
    }

    usort($resultats, function ($a, $b) {
        $timeA = strtotime($a['date_creation']);
        $timeB = strtotime($b['date_creation']);
        return $timeA === $timeB ? 0 : ($timeA < $timeB ? 1 : -1);
    });

    return $resultats;
}

/**
 * Affiche la liste des organisateurs et leurs chasses dans un tableau.
 *
 * @param array|null $liste Données pré-calculées.
 * @param int        $page  Page courante.
 * @param int        $per_page Nombre d'organisateurs par page.
 */
function afficher_tableau_organisateurs_pending(
    ?array $liste = null,
    int $page = 1,
    int $per_page = ORGANISATEURS_PENDING_PER_PAGE
): void {
    if (null === $liste) {
        $liste = recuperer_organisateurs_pending();
    }
    if (empty($liste)) {
        echo '<p>' . esc_html__('Aucun organisateur.', 'chassesautresor-com') . '</p>';
        return;
    }

    $grouped = [];
    foreach ($liste as $entry) {
        $oid = $entry['organisateur_id'];
        if (!isset($grouped[$oid])) {
            $grouped[$oid] = [
                'organisateur_titre'     => $entry['organisateur_titre'],
                'organisateur_permalink' => $entry['organisateur_permalink'],
                'user_id'                => $entry['user_id'],
                'user_name'              => $entry['user_name'],
                'user_link'              => $entry['user_link'],
                'rows'                   => [],
            ];
        }
        $grouped[$oid]['rows'][] = $entry;
    }

    $total  = count($grouped);
    $pages  = max(1, (int) ceil($total / $per_page));
    $page   = max(1, min($page, $pages));
    $offset = ($page - 1) * $per_page;
    $grouped = array_slice($grouped, $offset, $per_page, true);

    echo '<div class="stats-table-wrapper" data-per-page="' . intval($per_page) . '">';
    echo '<table class="stats-table table-organisateurs">';
    echo '<thead><tr>';
    echo '<th scope="col">' . esc_html__('Organisateur', 'chassesautresor-com') . '</th>';
    echo '<th scope="col">' . esc_html__('Chasse', 'chassesautresor-com') . '</th>';
    echo '<th scope="col" data-format="etiquette"><span class="etiquette">'
        . esc_html__('Nb énigmes', 'chassesautresor-com') . '</span></th>';
    echo '<th scope="col">' . esc_html__('État', 'chassesautresor-com') . '</th>';
    echo '<th scope="col">' . esc_html__('Utilisateur', 'chassesautresor-com') . '</th>';
    echo '<th scope="col" data-col="date">' . esc_html__('Créé le', 'chassesautresor-com')
        . ' <span class="tri-date">&#9650;&#9660;</span></th>';
    echo '</tr></thead><tbody>';

    foreach ($grouped as $org) {
        $rows    = $org['rows'];
        $rowspan = count($rows);
        $first   = true;
        foreach ($rows as $row) {
            echo '<tr data-etat="' . esc_attr($row['statut']) . '" data-date="'
                . esc_attr($row['date_creation']) . '">';
            if ($first) {
                echo '<td rowspan="' . intval($rowspan) . '"><a href="'
                    . esc_url($org['organisateur_permalink']) . '" target="_blank">'
                    . esc_html($org['organisateur_titre']) . '</a></td>';
            }

            if ($row['chasse_id']) {
                $statut      = $row['statut'];
                $badge_class = 'statut-revision';

                switch ($statut) {
                    case 'valide':
                        $badge_class  = 'statut-en_cours';
                        $statut_label = __('valide', 'chassesautresor-com');
                        break;
                    case 'correction':
                        $statut_label = __('correction', 'chassesautresor-com');
                        break;
                    case 'en_attente':
                        $statut_label = __('en attente', 'chassesautresor-com');
                        break;
                    case 'creation':
                        $statut_label = __('création', 'chassesautresor-com');
                        break;
                    case 'banni':
                        $badge_class  = 'statut-termine';
                        $statut_label = __('banni', 'chassesautresor-com');
                        break;
                    default:
                        $statut_label = $statut;
                        break;
                }

                echo '<td class="col-chasse"><a href="' . esc_url($row['chasse_permalink']) . '">'
                    . esc_html($row['chasse_titre'])
                    . '</a></td>';
                echo '<td class="col-enigmes"><span class="etiquette">'
                    . intval($row['nb_enigmes']) . '</span></td>';

                $warning   = $row['pending_validation'] || $row['pending_attempts'];
                $tooltip   = '';
                if ($warning) {
                    if ($row['pending_validation'] && $row['pending_attempts']) {
                        $tooltip = __(
                            'Demande de validation et tentatives manuelles en attente',
                            'chassesautresor-com'
                        );
                    } elseif ($row['pending_validation']) {
                        $tooltip = __('Demande de validation en attente', 'chassesautresor-com');
                    } else {
                        $tooltip = __('Tentatives manuelles en attente de réponse', 'chassesautresor-com');
                    }
                }

                echo '<td data-col="etat"><span class="badge-statut ' . esc_attr($badge_class) . '">'
                    . esc_html($statut_label) . '</span>';
                if ($warning) {
                    echo '<span class="required" aria-hidden="true" title="' . esc_attr($tooltip) . '">*</span>';
                }
                echo '</td>';
            } else {
                echo '<td class="col-chasse">-</td><td class="col-enigmes">'
                    . '<span class="etiquette">-</span></td><td data-col="etat"></td>';
            }

            if ($first) {
                if ($org['user_id']) {
                    echo '<td rowspan="' . intval($rowspan) . '"><a href="'
                        . esc_url($org['user_link']) . '" target="_blank">'
                        . esc_html($org['user_name']) . '</a></td>';
                } else {
                    echo '<td rowspan="' . intval($rowspan) . '">-</td>';
                }
            }

            echo '<td>' . esc_html(date_i18n('d/m/y', strtotime($row['date_creation']))) . '</td>';
            echo '</tr>';
            $first = false;
        }
    }

    echo '</tbody></table>';
    echo cta_render_pager($page, $pages, 'organisateurs-pager');
    echo '</div>';
}
