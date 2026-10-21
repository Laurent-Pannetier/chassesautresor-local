<?php

/**
 * Partial : chasse-partial-sommaire-enigmes.php
 *
 * Sommaire d'album : une ligne par énigme (vignette, titre, pointillés, numéro).
 * Le joueur ne voit que les énigmes dont l'état d'accessibilité, déjà calculé
 * par get_cta_enigme() à partir des prérequis, vaut « accessible ».
 */

defined('ABSPATH') || exit;

$chasse_id = $args['chasse_id'] ?? null;
if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') {
    return;
}

$infos_chasse = $args['infos_chasse'] ?? preparer_infos_affichage_chasse($chasse_id);
$utilisateur_id = (int) get_current_user_id();

if (!chasse_est_visible_pour_utilisateur($chasse_id, $utilisateur_id)) {
    return;
}

$est_orga_associe = $args['est_orga_associe']
    ?? utilisateur_est_organisateur_associe_a_chasse($utilisateur_id, $chasse_id);
$show_help_icon = $args['show_help_icon'] ?? false;
$statut_metier = $infos_chasse['statut'] ?? 'revision';
$est_privilegie = user_can($utilisateur_id, 'manage_options') || $est_orga_associe;

$autorise_boucle = (
    $est_privilegie
    || utilisateur_est_engage_dans_chasse($utilisateur_id, $chasse_id)
    || $statut_metier === 'termine'
);
if (!$autorise_boucle) {
    return;
}

$ids_bruts = $infos_chasse['enigmes_associees'] ?? [];
$ids_enigmes = is_array($ids_bruts) ? array_map('intval', $ids_bruts) : [];
$posts = [];
if ($ids_enigmes !== []) {
    $posts = get_posts([
        'post_type' => 'enigme',
        'post_status' => ['publish', 'pending', 'draft'],
        'posts_per_page' => count($ids_enigmes),
        'post__in' => $ids_enigmes,
        'orderby' => 'post__in',
    ]);
}

$posts_par_id = [];
foreach ($posts as $enigme_post) {
    $posts_par_id[(int) $enigme_post->ID] = $enigme_post;
}

$peut_reordonner = in_array(
    $infos_chasse['statut_validation'] ?? '',
    ['creation', 'correction'],
    true
) && $est_privilegie;

$has_incomplete = false;
if (
    ($peut_reordonner || user_can($utilisateur_id, 'manage_options'))
    && function_exists('verifier_ou_mettre_a_jour_cache_complet')
) {
    foreach ($posts as $enigme_post) {
        verifier_ou_mettre_a_jour_cache_complet($enigme_post->ID);
        if (!get_field('enigme_cache_complet', $enigme_post->ID)) {
            $has_incomplete = true;
            break;
        }
    }
}

$lignes = [];
$numero = 0;
foreach ($ids_enigmes as $enigme_id) {
    $numero++;
    if (!isset($posts_par_id[$enigme_id]) || !function_exists('get_cta_enigme')) {
        continue;
    }

    $cta = get_cta_enigme($enigme_id, $utilisateur_id);
    $etat = (string) ($cta['etat_systeme'] ?? '');
    $visible = function_exists('cat_sommaire_enigme_est_visible')
        ? cat_sommaire_enigme_est_visible($etat, (bool) $est_privilegie)
        : ($est_privilegie || $etat === 'accessible');
    if (!$visible) {
        continue;
    }

    $url = '';
    if (($cta['action'] ?? '') === 'link' && !empty($cta['url'])) {
        $url = (string) $cta['url'];
    } elseif (function_exists('get_permalink')) {
        $url = (string) get_permalink($enigme_id);
    }
    if ($url === '') {
        continue;
    }

    $titre = function_exists('get_the_title') ? (string) get_the_title($enigme_id) : '';
    if ($titre === '') {
        $titre = __('Énigme', 'chassesautresor-com');
    }

    $image_id = 0;
    $images = get_field('enigme_visuel_image', $enigme_id, false);
    if (is_array($images) && isset($images[0])) {
        $image_id = (int) $images[0];
    }

    $statut_utilisateur = (string) ($cta['statut_utilisateur'] ?? '');
    $lignes[] = [
        'id' => $enigme_id,
        'numero' => $numero,
        'titre' => $titre,
        'url' => $url,
        'image_id' => $image_id,
        'trouvee' => in_array($statut_utilisateur, ['resolue', 'terminee'], true),
    ];
}
?>
<div class="sommaire-bd">
    <h3 class="sommaire-bd__kicker"><?php esc_html_e('Sommaire', 'chassesautresor-com'); ?></h3>
    <?php if ($lignes === []) : ?>
        <p class="sommaire-bd__vide">
            <?php
            if ($ids_enigmes === []) {
                esc_html_e('Le sommaire est encore vide.', 'chassesautresor-com');
            } else {
                esc_html_e(
                    'Aucune énigme n’est accessible pour le moment.',
                    'chassesautresor-com'
                );
            }
            ?>
        </p>
    <?php else : ?>
        <ul class="sommaire-bd__liste">
            <?php foreach ($lignes as $ligne) : ?>
                <li class="sommaire-bd__item" data-enigme-id="<?php echo esc_attr((string) $ligne['id']); ?>">
                    <a class="sommaire-bd__lien" href="<?php echo esc_url($ligne['url']); ?>">
                        <span class="sommaire-bd__vignette">
                            <?php if ($ligne['image_id'] > 0 && function_exists('build_picture_enigme')) : ?>
                                <?php
                                echo build_picture_enigme(
                                    $ligne['image_id'],
                                    '',
                                    ['thumbnail'],
                                    ['sizes' => '5rem']
                                );
                                ?>
                            <?php else : ?>
                                <span class="sommaire-bd__case" aria-hidden="true"></span>
                            <?php endif; ?>
                        </span>
                        <span class="sommaire-bd__titre"><?php echo esc_html($ligne['titre']); ?></span>
                        <span class="sommaire-bd__pointilles" aria-hidden="true"></span>
                        <span class="sommaire-bd__numero"><?php echo esc_html((string) $ligne['numero']); ?></span>
                        <?php if ($ligne['trouvee']) : ?>
                            <span class="screen-reader-text">
                                <?php esc_html_e('Énigme trouvée.', 'chassesautresor-com'); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php
if (
    function_exists('utilisateur_peut_ajouter_enigme')
    && utilisateur_peut_ajouter_enigme($chasse_id, $utilisateur_id)
    && !$has_incomplete
) {
    $highlight_pulse = false;
    if ($ids_enigmes === []) {
        $wp_status = get_post_status($chasse_id);
        $statut_validation = $infos_chasse['statut_validation'] ?? '';
        if (
            $wp_status === 'pending'
            && $statut_metier === 'revision'
            && in_array($statut_validation, ['creation', 'correction'], true)
        ) {
            $highlight_pulse = true;
        }
    }

    echo '<div class="sommaire-bd-ajout">';
    get_template_part('template-parts/enigme/chasse-partial-ajout-enigme', null, [
        'has_enigmes' => $ids_enigmes !== [],
        'chasse_id' => $chasse_id,
        'highlight_pulse' => $highlight_pulse,
        'use_button' => false,
        'show_help_icon' => $show_help_icon,
    ]);
    echo '</div>';
}
