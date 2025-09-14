<?php
defined('ABSPATH') || exit;

if (!isset($args['chasse_id']) || empty($args['chasse_id'])) {
    return;
}

$chasse_id       = (int) $args['chasse_id'];
$completion_class = $args['completion_class'] ?? '';
$word_limit      = isset($args['word_limit']) ? (int) $args['word_limit'] : 300;
$infos           = preparer_infos_affichage_carte_chasse($chasse_id, $word_limit);
$mode_fin        = $infos['mode_fin'] ?? 'automatique';
$title_mode      = $mode_fin === 'automatique'
    ? esc_html__('mode de fin de chasse : automatique', 'chassesautresor-com')
    : esc_html__('mode de fin de chasse : manuelle', 'chassesautresor-com');

$orga_id = get_organisateur_from_chasse($chasse_id);
$orga_logo_url = '';

if ($orga_id) {
    $orga_logo_id  = get_field('logo_organisateur', $orga_id, false);
    $orga_logo     = wp_get_attachment_image_src($orga_logo_id, 'thumbnail');
    $orga_logo_url = $orga_logo ? $orga_logo[0] : wp_get_attachment_image_src(3927, 'thumbnail')[0];
}

if (empty($infos)) {
    return;
}
?>
<div class="carte carte-chasse carte-wide <?php echo esc_attr(trim($infos['classe_statut'] . ' ' . $completion_class)); ?>">
    <div class="carte-wide__image">
        <span class="badge-statut <?php echo esc_attr($infos['badge_class']); ?>"
            data-post-id="<?php echo esc_attr($chasse_id); ?>">
            <?php echo esc_html($infos['statut_label']); ?>
        </span>
        <?php if ((int) $infos['cout_points'] > 0) : ?>
        <span
            class="badge-cout"
            data-post-id="<?php echo esc_attr($chasse_id); ?>"
            aria-label="<?php echo esc_attr(
                sprintf(
                    __('Coût de participation : %d points.', 'chassesautresor-com'),
                    $infos['cout_points']
                )
            ); ?>"
        >
            <?php echo esc_html($infos['cout_points'] . ' ' . __('pts', 'chassesautresor-com')); ?>
        </span>
        <?php endif; ?>

        <span class="mode-fin-icone" title="<?php echo esc_attr($title_mode); ?>" aria-label="<?php echo esc_attr($title_mode); ?>">
            <?php if ($mode_fin === 'automatique') : ?>
                <i class="fa-solid fa-bolt"></i>
            <?php else : ?>
                <?php echo get_svg_icon('hand'); ?>
            <?php endif; ?>
        </span>
        <?php
        $image_id   = $infos['image_id'] ?? 0;
        if ($image_id) {
            $image_html = wp_get_attachment_image(
                $image_id,
                [300, 300],
                false,
                [
                    'alt'     => $infos['titre'],
                    'loading' => 'lazy',
                ]
            );
        } else {
            $image_html = sprintf(
                '<img src="%s" alt="%s" loading="lazy">',
                esc_url($infos['image']),
                esc_attr($infos['titre'])
            );
        }
        ?>
        <a class="carte-wide__image-link" href="<?php echo esc_url($infos['permalink']); ?>">
            <?php echo $image_html; ?>
        </a>
        </div>

    <div class="carte-wide__contenu">
        <div class="carte-wide__header">
            <h3 class="carte-wide__titre">
                <a href="<?php echo esc_url($infos['permalink']); ?>"><?php echo esc_html($infos['titre']); ?></a>
            </h3>
        </div>

        <div class="carte-wide__content">
            <div class="meta-row svg-xsmall">
                <div class="meta-regular">
                    <?php echo get_svg_icon('enigme'); ?>
                    <?php
                    echo esc_html(
                        sprintf(
                            _n('%d énigme', '%d énigmes', $infos['total_enigmes'], 'chassesautresor-com'),
                            $infos['total_enigmes']
                        )
                    );
                    ?> —
                    <?php echo get_svg_icon('participants'); ?><?php echo esc_html($infos['nb_joueurs_label']); ?>
                </div>
                <div class="meta-etiquette">
                    <?php echo get_svg_icon('calendar'); ?>
                    <span class="chasse-date-plage">
                        <span class="date-debut"><?php echo esc_html($infos['date_debut']); ?></span> –
                        <span class="date-fin"><?php echo esc_html($infos['date_fin']); ?></span>
                    </span>
                </div>
            </div>

            <?php echo $infos['extrait_html']; ?>
        </div>

        <?php if ($orga_id) : ?>
            <?php
            $reward_title = get_field('chasse_infos_recompense_titre', $chasse_id);
            $reward_value = get_field('chasse_infos_recompense_valeur', $chasse_id);
            ?>
            <div class="carte-wide__footer">
                <footer class="chasse-footer">
                    <?php if (!empty($reward_title) && (float) $reward_value > 0) : ?>
                        <span class="chasse-footer__reward">
                            <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                            <?php
                            printf(
                                esc_html__('%1$s — %2$s €', 'chassesautresor-com'),
                                esc_html($reward_title),
                                esc_html(number_format_i18n(round((float) $reward_value), 0))
                            );
                            ?>
                        </span>
                    <?php endif; ?>
                    <span class="chasse-footer__texte">
                        <?php echo esc_html__('Proposé par', 'chassesautresor-com'); ?>
                        <a class="chasse-footer__logo-link" href="<?php echo esc_url(get_permalink($orga_id)); ?>">
                            <img
                                class="chasse-organisateur__logo chasse-footer__logo visuel-cpt"
                                src="<?php echo esc_url($orga_logo_url); ?>"
                                alt="<?php echo esc_attr__('Logo de l\u2019organisateur', 'chassesautresor-com'); ?>"
                                data-cpt="organisateur"
                                data-post-id="<?php echo esc_attr($orga_id); ?>"
                            />
                        </a>
                        <a class="chasse-footer__nom" href="<?php echo esc_url(get_permalink($orga_id)); ?>">
                            <?php echo esc_html(get_the_title($orga_id)); ?>
                        </a>
                    </span>
                </footer>
            </div>
        <?php endif; ?>
    </div>
</div>
