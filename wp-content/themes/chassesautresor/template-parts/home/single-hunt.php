<?php
/**
 * Editorial homepage for a site dedicated to one treasure hunt.
 */

defined('ABSPATH') || exit;

$huntId = function_exists('cat_get_primary_hunt_id') ? cat_get_primary_hunt_id() : 0;
$isDemoMode = function_exists('cat_is_demo_mode') && cat_is_demo_mode();

get_header();

if ($huntId <= 0 || get_post_type($huntId) !== 'chasse') {
    ?>
    <main id="home-page" class="single-hunt-home single-hunt-home--empty">
        <section class="single-hunt-home__empty conteneur" aria-labelledby="single-hunt-empty-title">
            <p class="single-hunt-home__eyebrow"><?php esc_html_e('Prochainement', 'chassesautresor-com'); ?></p>
            <h1 id="single-hunt-empty-title">
                <?php esc_html_e('Une nouvelle chasse au trésor se prépare.', 'chassesautresor-com'); ?>
            </h1>
            <p>
                <?php esc_html_e(
                    'Revenez bientôt pour découvrir l’aventure et ses premières énigmes.',
                    'chassesautresor-com'
                ); ?>
            </p>
        </section>
    </main>
    <?php
    get_footer();
    return;
}

$userId = get_current_user_id();
$huntInfo = preparer_infos_affichage_chasse($huntId, $userId);
$fields = $huntInfo['champs'] ?? [];
$status = (string) ($huntInfo['statut'] ?? 'revision');
$description = (string) ($huntInfo['description'] ?? '');
$riddleIds = array_map('intval', (array) ($huntInfo['enigmes_associees'] ?? []));
$riddleCount = count($riddleIds);
$cost = max(0, (int) ($fields['cout_points'] ?? 0));
$startDate = (string) ($fields['date_debut'] ?? '');
$endDate = (string) ($fields['date_fin'] ?? '');
$unlimited = !empty($fields['illimitee']);
$rewardTitle = trim((string) ($fields['titre_recompense'] ?? ''));
$huntUrl = get_permalink($huntId);
$cta = function_exists('generer_cta_chasse')
    ? generer_cta_chasse($huntId, $userId)
    : ['cta_html' => '', 'cta_message' => '', 'type' => ''];

if (($cta['type'] ?? '') === 'engage') {
    $cta['cta_html'] = sprintf(
        '<a href="%s" class="bouton-cta bouton-cta--color">%s</a>',
        esc_url($huntUrl . '#chasse-enigmes-wrapper'),
        esc_html__('Reprendre ma progression', 'chassesautresor-com')
    );
} elseif ($isDemoMode && empty($cta['cta_html'])) {
    $cta = [
        'cta_html' => sprintf(
            '<a href="#home-enigmes" class="bouton-cta bouton-cta--color">%s</a>',
            esc_html__('Découvrir l’aperçu', 'chassesautresor-com')
        ),
        'cta_message' => '',
        'type' => 'demo',
    ];
}

$statusLabels = [
    'a_venir' => __('À venir', 'chassesautresor-com'),
    'en_cours' => __('En cours', 'chassesautresor-com'),
    'payante' => __('En cours', 'chassesautresor-com'),
    'termine' => __('Terminée', 'chassesautresor-com'),
    'revision' => __('En préparation', 'chassesautresor-com'),
];
$statusLabel = $statusLabels[$status] ?? __('En préparation', 'chassesautresor-com');
$startLabel = $startDate ? formater_date($startDate) : __('Bientôt annoncée', 'chassesautresor-com');
$endLabel = $unlimited
    ? __('Sans date de fin', 'chassesautresor-com')
    : ($endDate ? formater_date($endDate) : __('À confirmer', 'chassesautresor-com'));
$costLabel = $cost > 0
    ? sprintf(_n('%d point', '%d points', $cost, 'chassesautresor-com'), $cost)
    : __('Gratuite', 'chassesautresor-com');
$riddleLabel = sprintf(
    _n('%d énigme', '%d énigmes', $riddleCount, 'chassesautresor-com'),
    $riddleCount
);
$canShowRiddles = current_user_can('manage_options')
    || utilisateur_est_organisateur_associe_a_chasse($userId, $huntId)
    || utilisateur_est_engage_dans_chasse($userId, $huntId)
    || $status === 'termine';
$frontPage = get_queried_object();
$editorialContent = $frontPage instanceof WP_Post
    ? trim((string) apply_filters('the_content', $frontPage->post_content))
    : '';
?>

<main id="home-page" class="single-hunt-home">
    <?php if ($isDemoMode) : ?>
        <div class="single-hunt-home__demo-notice" role="status">
            <div class="conteneur">
                <?php
                esc_html_e(
                    'Mode démo : cette chasse est présentée sur l’accueil avant sa publication officielle.',
                    'chassesautresor-com'
                );
                ?>
            </div>
        </div>
    <?php endif; ?>
    <section class="single-hunt-home__facts" aria-labelledby="single-hunt-facts-title">
        <div class="conteneur">
            <p class="single-hunt-home__eyebrow"><?php esc_html_e('L’aventure en bref', 'chassesautresor-com'); ?></p>
            <h2 id="single-hunt-facts-title" class="screen-reader-text">
                <?php esc_html_e('Informations essentielles sur la chasse', 'chassesautresor-com'); ?>
            </h2>
            <dl class="single-hunt-facts">
                <div class="single-hunt-fact">
                    <dt><?php esc_html_e('Statut', 'chassesautresor-com'); ?></dt>
                    <dd><?php echo esc_html($statusLabel); ?></dd>
                </div>
                <div class="single-hunt-fact">
                    <dt><?php esc_html_e('Début', 'chassesautresor-com'); ?></dt>
                    <dd><?php echo esc_html($startLabel); ?></dd>
                </div>
                <div class="single-hunt-fact">
                    <dt><?php esc_html_e('Fin', 'chassesautresor-com'); ?></dt>
                    <dd><?php echo esc_html($endLabel); ?></dd>
                </div>
                <div class="single-hunt-fact">
                    <dt><?php esc_html_e('Participation', 'chassesautresor-com'); ?></dt>
                    <dd><?php echo esc_html($costLabel); ?></dd>
                </div>
                <div class="single-hunt-fact">
                    <dt><?php esc_html_e('Parcours', 'chassesautresor-com'); ?></dt>
                    <dd><?php echo esc_html($riddleLabel); ?></dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="single-hunt-home__story" aria-labelledby="single-hunt-story-title">
        <div class="conteneur single-hunt-home__story-grid">
            <div>
                <p class="single-hunt-home__eyebrow"><?php esc_html_e('Votre mission', 'chassesautresor-com'); ?></p>
                <h2 id="single-hunt-story-title"><?php echo esc_html(get_the_title($huntId)); ?></h2>
                <?php if ($description !== '') : ?>
                    <div class="single-hunt-home__description">
                        <?php echo wp_kses_post(apply_filters('the_content', $description)); ?>
                    </div>
                <?php endif; ?>
                <?php if ($rewardTitle !== '') : ?>
                    <p class="single-hunt-home__reward">
                        <span aria-hidden="true">✦</span>
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: %s: reward title. */
                                __('À la clé : %s', 'chassesautresor-com'),
                                $rewardTitle
                            )
                        );
                        ?>
                    </p>
                <?php endif; ?>
                <div class="single-hunt-home__actions">
                    <?php
                    echo $cta['cta_html'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                    <a class="single-hunt-home__details-link" href="<?php echo esc_url($huntUrl); ?>">
                        <?php esc_html_e('Voir tous les détails', 'chassesautresor-com'); ?>
                    </a>
                </div>
                <?php if (!empty($cta['cta_message'])) : ?>
                    <div class="single-hunt-home__cta-message">
                        <?php echo wp_kses_post($cta['cta_message']); ?>
                    </div>
                <?php endif; ?>
            </div>
            <ol
                class="single-hunt-steps"
                aria-label="<?php esc_attr_e('Comment participer', 'chassesautresor-com'); ?>"
            >
                <li>
                    <span>1</span>
                    <div>
                        <h3><?php esc_html_e('Rejoignez la chasse', 'chassesautresor-com'); ?></h3>
                        <p>
                            <?php
                            esc_html_e(
                                'Connectez-vous puis confirmez votre participation.',
                                'chassesautresor-com'
                            );
                            ?>
                        </p>
                    </div>
                </li>
                <li>
                    <span>2</span>
                    <div>
                        <h3><?php esc_html_e('Explorez les énigmes', 'chassesautresor-com'); ?></h3>
                        <p>
                            <?php
                            esc_html_e(
                                'Progressez à votre rythme et débloquez la suite du parcours.',
                                'chassesautresor-com'
                            );
                            ?>
                        </p>
                    </div>
                </li>
                <li>
                    <span>3</span>
                    <div>
                        <h3><?php esc_html_e('Trouvez le trésor', 'chassesautresor-com'); ?></h3>
                        <p>
                            <?php
                            esc_html_e(
                                'Rassemblez vos découvertes pour aller au bout de l’aventure.',
                                'chassesautresor-com'
                            );
                            ?>
                        </p>
                    </div>
                </li>
            </ol>
        </div>
    </section>

    <?php if ($editorialContent !== '') : ?>
        <section
            class="single-hunt-home__editorial"
            aria-label="<?php esc_attr_e('À propos de la chasse', 'chassesautresor-com'); ?>"
        >
            <div class="conteneur entry-content">
                <?php echo $editorialContent; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </section>
    <?php endif; ?>

    <section id="home-enigmes" class="single-hunt-home__riddles" aria-labelledby="single-hunt-riddles-title">
        <div class="conteneur">
            <div class="single-hunt-home__section-heading">
                <div>
                    <p class="single-hunt-home__eyebrow"><?php esc_html_e('Le parcours', 'chassesautresor-com'); ?></p>
                    <h2 id="single-hunt-riddles-title">
                        <?php esc_html_e('Les énigmes vous attendent', 'chassesautresor-com'); ?>
                    </h2>
                </div>
                <a href="<?php echo esc_url($huntUrl . '#chasse-enigmes-wrapper'); ?>">
                    <?php esc_html_e('Accéder aux énigmes', 'chassesautresor-com'); ?>
                </a>
            </div>
            <?php if ($canShowRiddles) : ?>
                <?php
                get_template_part(
                    'template-parts/enigme/chasse-partial-boucle-enigmes',
                    null,
                    [
                        'chasse_id' => $huntId,
                        'infos_chasse' => $huntInfo,
                        'show_help_icon' => false,
                    ]
                );
                ?>
            <?php else : ?>
                <div class="single-hunt-home__riddles-teaser">
                    <span class="single-hunt-home__riddles-count"><?php echo esc_html((string) $riddleCount); ?></span>
                    <div>
                        <h3><?php echo esc_html($riddleLabel); ?></h3>
                        <p>
                            <?php esc_html_e(
                                'Rejoignez la chasse pour révéler les cartes d’énigmes '
                                    . 'et commencer votre progression.',
                                'chassesautresor-com'
                            ); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="single-hunt-home__final-cta" aria-labelledby="single-hunt-final-title">
        <div class="conteneur">
            <p class="single-hunt-home__eyebrow"><?php esc_html_e('Prêt à partir ?', 'chassesautresor-com'); ?></p>
            <h2 id="single-hunt-final-title">
                <?php esc_html_e('Votre prochaine découverte commence ici.', 'chassesautresor-com'); ?>
            </h2>
            <div class="single-hunt-home__actions single-hunt-home__actions--centered">
                <?php echo $cta['cta_html'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
