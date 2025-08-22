<?php
/**
 * Template Part: Panneau d'édition frontale d'un indice
 * Requiert : $args['indice_id']
 */

defined('ABSPATH') || exit;

$indice_id = $args['indice_id'] ?? null;
if (!$indice_id || get_post_type($indice_id) !== 'indice') {
    return;
}

$peut_modifier = utilisateur_peut_voir_panneau($indice_id);
if (!$peut_modifier) {
    return;
}

$titre          = get_the_title($indice_id);
$image_id       = get_field('indice_image', $indice_id);
$image_url      = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
$texte          = get_field('indice_contenu', $indice_id);
$cible          = get_field('indice_cible', $indice_id) ?: 'chasse';
$cible_objet    = (array) get_field('indice_cible_objet', $indice_id, false);
$chasse_liee    = get_field('indice_chasse_linked', $indice_id, false);
$disponibilite  = get_field('indice_disponibilite', $indice_id) ?: 'immediate';
$date_dispo     = get_field('indice_date_disponibilite', $indice_id);
$cout           = (int) get_field('indice_cout_points', $indice_id);

$enigmes_eligibles = [];
$enigmes_posts     = get_posts([
    'post_type'      => 'enigme',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'title',
    'order'          => 'ASC',
]);
foreach ($enigmes_posts as $enigma) {
    $chasse = get_field('enigme_chasse_associee', $enigma->ID, false);
    $chasse_id = is_object($chasse) ? $chasse->ID : (int) $chasse;
    if ($chasse_id && get_post_status($chasse_id) === 'publish') {
        $enigmes_eligibles[$enigma->ID] = get_the_title($enigma->ID);
    }
}
$selection = array_map('intval', $cible_objet);
?>

<section class="edition-panel edition-panel-indice edition-panel-modal" data-cpt="indice" data-post-id="<?= esc_attr($indice_id); ?>">
    <div class="edition-panel-header">
        <div class="edition-panel-header-top">
            <h2>
                <i class="fa-solid fa-gear"></i>
                <?= esc_html__("Panneau d'édition indice", 'chassesautresor-com'); ?> :
                <span class="titre-objet" data-cpt="indice"><?= esc_html($titre); ?></span>
            </h2>
            <button type="button" class="panneau-fermer" aria-label="<?= esc_attr__('Fermer les paramètres', 'chassesautresor-com'); ?>">✖</button>
        </div>
        <div class="edition-tabs">
            <button class="edition-tab active" data-target="indice-tab-param"><?= esc_html__('Paramètres', 'chassesautresor-com'); ?></button>
        </div>
    </div>

    <div id="indice-tab-param" class="edition-tab-content active">
        <i class="fa-solid fa-sliders tab-watermark" aria-hidden="true"></i>
        <div class="edition-panel-header">
            <h2><i class="fa-solid fa-sliders"></i> <?= esc_html__('Paramètres', 'chassesautresor-com'); ?></h2>
        </div>
        <div class="edition-panel-body">
            <div class="edition-panel-section edition-panel-section-ligne">
                <div class="section-content">
                    <div class="resume-blocs-grid">
                        <div class="resume-bloc resume-obligatoire">
                            <h3><?= esc_html__('Informations', 'chassesautresor-com'); ?></h3>
                            <ul class="resume-infos">
                                <?php
                                get_template_part(
                                    'template-parts/common/edition-row',
                                    null,
                                    [
                                        'class' => 'champ-indice champ-titre ' . (empty($titre) ? 'champ-vide' : 'champ-rempli'),
                                        'attributes' => [
                                            'data-champ'   => 'post_title',
                                            'data-cpt'     => 'indice',
                                            'data-post-id' => $indice_id,
                                            'data-no-edit' => '1',
                                        ],
                                        'label' => function () {
                                            ?>
                                            <label for="champ-titre-indice"><?= esc_html__('Titre', 'chassesautresor-com'); ?> <span class="champ-obligatoire">*</span></label>
                                            <?php
                                        },
                                        'content' => function () use ($titre) {
                                            ?>
                                            <input type="text" class="champ-input champ-texte-edit" maxlength="80" value="<?= esc_attr($titre); ?>" id="champ-titre-indice" placeholder="<?= esc_attr__('renseigner le titre de l’indice', 'chassesautresor-com'); ?>" />
                                            <div class="champ-feedback"></div>
                                            <?php
                                        },
                                    ]
                                );
                                ?>

                                <li class="champ-indice champ-img <?= empty($image_id) ? 'champ-vide' : 'champ-rempli'; ?>" data-champ="indice_image" data-cpt="indice" data-post-id="<?= esc_attr($indice_id); ?>">
                                    <div class="champ-affichage">
                                        <label><?= esc_html__('Image', 'chassesautresor-com'); ?> <span class="champ-obligatoire">*</span></label>
                                        <?php if ($peut_modifier) : ?>
                                            <button type="button" class="champ-modifier" data-champ="indice_image" data-cpt="indice" data-post-id="<?= esc_attr($indice_id); ?>" aria-label="<?= esc_attr__('Modifier l’image', 'chassesautresor-com'); ?>">
                                                <img src="<?= esc_url($image_url ?: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='); ?>" alt="" />
                                                <span class="champ-ajout-image"><?= esc_html__('ajouter une image', 'chassesautresor-com'); ?></span>
                                            </button>
                                        <?php else : ?>
                                            <?php if ($image_url) : ?>
                                                <img src="<?= esc_url($image_url); ?>" alt="" />
                                            <?php else : ?>
                                                <span class="champ-ajout-image"><?= esc_html__('ajouter une image', 'chassesautresor-com'); ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" class="champ-input" value="<?= esc_attr($image_id ?? ''); ?>">
                                    <div class="champ-feedback"></div>
                                </li>

                                <li class="champ-indice champ-description <?= empty($texte) ? 'champ-vide' : 'champ-rempli'; ?><?= $peut_modifier ? '' : ' champ-desactive'; ?>" data-champ="indice_contenu" data-cpt="indice" data-post-id="<?= esc_attr($indice_id); ?>">
                                    <label><?= esc_html__('Texte', 'chassesautresor-com'); ?> <span class="champ-obligatoire">*</span></label>
                                    <div class="champ-texte">
                                        <?php if (empty(trim($texte))) : ?>
                                            <?php if ($peut_modifier) : ?>
                                                <a href="#" class="champ-ajouter ouvrir-panneau-description" data-cpt="indice" data-champ="indice_contenu" data-post-id="<?= esc_attr($indice_id); ?>">
                                                    <?= esc_html__('ajouter', 'chassesautresor-com'); ?>
                                                </a>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <span class="champ-texte-contenu">
                                                <?= esc_html(wp_trim_words(wp_strip_all_tags($texte), 25)); ?>
                                                <?php if ($peut_modifier) : ?>
                                                    <button type="button" class="champ-modifier ouvrir-panneau-description" data-cpt="indice" data-champ="indice_contenu" data-post-id="<?= esc_attr($indice_id); ?>" aria-label="<?= esc_attr__('Modifier le texte de l’indice', 'chassesautresor-com'); ?>">
                                                        <?= esc_html__('modifier', 'chassesautresor-com'); ?>
                                                    </button>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div class="resume-bloc resume-reglages">
                            <h3><?= esc_html__('Réglages', 'chassesautresor-com'); ?></h3>
                            <ul class="resume-infos">
                                <li class="champ-indice champ-cible <?= ($cible === 'enigme' && empty($selection)) ? 'champ-vide' : 'champ-rempli'; ?>" data-champ="indice_cible" data-cpt="indice" data-post-id="<?= esc_attr($indice_id); ?>">
                                    <label><?= esc_html__('Aide pour', 'chassesautresor-com'); ?></label>
                                    <div class="champ-radio">
                                        <label><input type="radio" name="acf[indice_cible]" value="chasse" <?= $cible === 'chasse' ? 'checked' : ''; ?>> <?= esc_html__('Chasse', 'chassesautresor-com'); ?></label>
                                        <label><input type="radio" name="acf[indice_cible]" value="enigme" <?= $cible === 'enigme' ? 'checked' : ''; ?>> <?= esc_html__('Énigmes', 'chassesautresor-com'); ?></label>
                                    </div>
                                    <div id="champ-indice-cible-enigmes" class="champ-indice champ-pre-requis<?= $cible === 'enigme' ? '' : ' cache'; ?><?= empty($enigmes_eligibles) ? ' champ-vide' : ''; ?>" data-champ="indice_cible_objet" data-cpt="indice" data-post-id="<?= esc_attr($indice_id); ?>" data-no-edit="1" data-chasse-id="<?= esc_attr($chasse_liee); ?>">
                                        <?php if (empty($enigmes_eligibles)) : ?>
                                            <em><?= esc_html__('Aucune énigme disponible.', 'chassesautresor-com'); ?></em>
                                        <?php else : ?>
                                            <div class="liste-pre-requis">
                                                <?php foreach ($enigmes_eligibles as $id => $titre_enigme) :
                                                    $img = get_image_enigme($id, 'thumbnail');
                                                    $checked = in_array($id, $selection, true);
                                                    ?>
                                                    <label class="prerequis-item">
                                                        <input type="checkbox" value="<?= esc_attr($id); ?>" <?= $checked ? 'checked' : ''; ?>>
                                                        <span class="prerequis-mini">
                                                            <?php if ($img) : ?>
                                                                <img src="<?= esc_url($img); ?>" alt="" />
                                                            <?php endif; ?>
                                                            <span class="prerequis-titre"><?= esc_html($titre_enigme); ?></span>
                                                            <span class="prerequis-check"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
                                                        </span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="champ-feedback"></div>
                                    </div>
                                    <div class="champ-feedback"></div>
                                </li>

                                <li class="champ-indice champ-publication <?= ($disponibilite === 'differe' && empty($date_dispo)) ? 'champ-vide' : 'champ-rempli'; ?>" data-champ="indice_disponibilite" data-cpt="indice" data-post-id="<?= esc_attr($indice_id); ?>">
                                    <label><?= esc_html__('Publication', 'chassesautresor-com'); ?></label>
                                    <div class="champ-radio">
                                        <label><input type="radio" name="acf[indice_disponibilite]" value="immediate" <?= $disponibilite === 'immediate' ? 'checked' : ''; ?>> <?= esc_html__('Immédiate', 'chassesautresor-com'); ?></label>
                                        <label><input type="radio" name="acf[indice_disponibilite]" value="differe" <?= $disponibilite === 'differe' ? 'checked' : ''; ?>> <?= esc_html__('Différée', 'chassesautresor-com'); ?></label>
                                    </div>
                                    <div id="champ-indice-date" class="<?= $disponibilite === 'differe' ? '' : 'cache'; ?>" data-champ="indice_date_disponibilite" data-cpt="indice" data-post-id="<?= esc_attr($indice_id); ?>" data-date="<?= esc_attr($date_dispo ?: ''); ?>">
                                        <input type="datetime-local" class="champ-input champ-date-edit" value="<?= esc_attr($date_dispo ?: ''); ?>">
                                        <div class="champ-feedback"></div>
                                    </div>
                                    <div class="champ-feedback"></div>
                                </li>

                                <li class="champ-indice champ-cout-points <?= empty($cout) ? 'champ-vide' : 'champ-rempli'; ?>" data-champ="indice_cout_points" data-cpt="indice" data-post-id="<?= esc_attr($indice_id); ?>">
                                    <div class="champ-edition" style="display:flex;align-items:center;">
                                        <label><?= esc_html__('Coût', 'chassesautresor-com'); ?> <span class="txt-small"><?= esc_html__('(points)', 'chassesautresor-com'); ?></span></label>
                                        <input type="number" class="champ-input champ-cout" min="0" step="1" value="<?= esc_attr($cout); ?>" placeholder="0" />
                                        <div class="champ-option-gratuit" style="margin-left:15px;">
                                            <input type="checkbox" <?= $cout === 0 ? 'checked' : ''; ?>>
                                            <label><?= esc_html__('Gratuit', 'chassesautresor-com'); ?></label>
                                        </div>
                                    </div>
                                    <div class="champ-feedback"></div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// 📎 Panneau latéral d'édition du texte
get_template_part('template-parts/indice/panneaux/indice-edition-description', null, [
    'indice_id' => $indice_id,
]);
?>
