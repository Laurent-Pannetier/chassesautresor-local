<?php
defined('ABSPATH') || exit;

// ==================================================
// 🖼️ AFFICHAGE DES VISUELS D’ÉNIGMES
/**
 * 🔹 define('ID_IMAGE_PLACEHOLDER_ENIGME', 3925) → Définit l’identifiant de l’image placeholder utilisée pour les énigmes.
 * 🔹 afficher_visuels_enigme() → Affiche l’image principale de l’énigme si l’utilisateur y a droit.
 * 🔹 get_image_enigme() → Renvoie l’URL de l’image principale d’une énigme ou un placeholder.
 * 🔹 enigme_a_une_image() → Vérifie si l’énigme a une image définie.
 * 🔹 get_url_vignette_enigme() → Retourne l’URL proxy de la première vignette d’une énigme.
 * 🔹 afficher_picture_vignette_enigme() → Affiche un bloc <picture> responsive pour une énigme.
 * 🔹 trouver_chemin_image() → Retourne le chemin absolu et le type MIME d’une image à une taille donnée.
 */

/**
 * Définit l'identifiant de l'image placeholder utilisée pour les énigmes.
 * 
 * Constante : ID_IMAGE_PLACEHOLDER_ENIGME
 * Valeur : 3925
 * 
 * Cette constante est utilisée comme identifiant de l'image par défaut (placeholder)
 * pour les énigmes dans le site WordPress.
 */
define('ID_IMAGE_PLACEHOLDER_ENIGME', 3925);


/**
 * Retourne le mapping des tailles d'image vers les requêtes media.
 *
 * @return array<string, string>
 */
function get_enigme_picture_breakpoints(): array
{
    return [
        'full'      => '(min-width: 1025px)',
        'large'     => '(min-width: 769px)',
        'medium'    => '(min-width: 481px)',
        'thumbnail' => '',
    ];
}

/**
 * Génère le HTML d'un bloc <picture> pour un ID d'image donné.
 *
 * @param int   $image_id     ID de l'image.
 * @param string $alt         Texte alternatif.
 * @param array $sizes        Tailles WordPress à utiliser (la plus grande en dernier).
 * @param array $img_attrs    Attributs supplémentaires pour la balise <img> finale.
 * @return string
 */
function build_picture_enigme(int $image_id, string $alt, array $sizes, array $img_attrs = []): string
{
    $breakpoints = get_enigme_picture_breakpoints();
    $order = ['thumbnail', 'medium', 'large', 'full'];

    $max_index = 0;
    foreach ($sizes as $s) {
        $idx = array_search($s, $order, true);
        if ($idx !== false && $idx > $max_index) {
            $max_index = $idx;
        }
    }

    $used_sizes = array_slice($order, 0, $max_index + 1);

    $base_url = site_url('/voir-image-enigme');

    $html = "<picture>\n";
    $size_2x_map = [
        'thumbnail' => 'medium',
        'medium'    => 'large',
        'large'     => 'full',
        'full'      => 'full',
    ];

    for ($i = count($used_sizes) - 1; $i > 0; $i--) {
        $size = $used_sizes[$i];
        $src_1x = esc_url(add_query_arg([
            'id'     => $image_id,
            'taille' => $size,
        ], $base_url));
        $src_2x = esc_url(add_query_arg([
            'id'     => $image_id,
            'taille' => $size_2x_map[$size] ?? $size,
        ], $base_url));
        $srcset_parts = [$src_1x . ' 1x'];
        if ($src_2x !== $src_1x) {
            $srcset_parts[] = $src_2x . ' 2x';
        }
        $srcset = implode(', ', $srcset_parts);
        $media = $breakpoints[$size];
        $media_attr = $media ? ' media="' . $media . '"' : '';
        $html .= '  <source srcset="' . $srcset . '" data-size="' . $size . '"' . $media_attr . ">\n";
    }

    $fallback_size = $used_sizes[0];
    $src_fallback_1x = esc_url(add_query_arg([
        'id'     => $image_id,
        'taille' => $fallback_size,
    ], $base_url));
    $src_fallback_2x = esc_url(add_query_arg([
        'id'     => $image_id,
        'taille' => $size_2x_map[$fallback_size] ?? $fallback_size,
    ], $base_url));

    $dimensions = function_exists('wp_get_attachment_image_src')
        ? wp_get_attachment_image_src($image_id, $fallback_size)
        : null;
    if (is_array($dimensions)) {
        $img_attrs['width']  = $dimensions[1];
        $img_attrs['height'] = $dimensions[2];
    }

    $img_attrs['loading'] = 'lazy';
    if (!isset($img_attrs['sizes'])) {
        $img_attrs['sizes'] = '(min-width:1025px) 400px, (min-width:769px) 300px, (min-width:481px) 200px, 100vw';
    }

    $attr_str = '';
    foreach ($img_attrs as $key => $value) {
        $attr_str .= ' ' . $key . '="' . esc_attr($value) . '"';
    }

    $img_srcset_parts = [$src_fallback_1x . ' 1x'];
    if ($src_fallback_2x !== $src_fallback_1x) {
        $img_srcset_parts[] = $src_fallback_2x . ' 2x';
    }
    $img_srcset = implode(', ', $img_srcset_parts);

    $html .= '  <img src="' . $src_fallback_1x . '" srcset="' . $img_srcset . '" alt="'
        . esc_attr($alt) . '"' . $attr_str . ">\n";
    $html .= "</picture>\n";

    return $html;
}

/**
 * Affiche une galerie d’images d’une énigme si l’utilisateur y a droit.
 *
 * Les images sont servies via proxy (/voir-image-enigme) avec tailles adaptées.
 *
 * @param int $enigme_id ID du post de type énigme
 * @return void
 */
function afficher_visuels_enigme(int $enigme_id): void
{
    if (!utilisateur_peut_voir_enigme($enigme_id)) {
        echo '<div class="visuels-proteges">🔒 Les visuels de cette énigme sont protégés.</div>';
        return;
    }

    $images = get_field('enigme_visuel_image', $enigme_id);
    $valid_images = [];
    if (is_array($images)) {
        foreach ($images as $img) {
            $id = (int) ($img['ID'] ?? 0);
            if ($id && $id !== ID_IMAGE_PLACEHOLDER_ENIGME) {
                $valid_images[] = $id;
            }
        }
    }

    if (!$valid_images) {
        $valid_images[] = ID_IMAGE_PLACEHOLDER_ENIGME;
    }

    $caption = (string) get_field('enigme_visuel_legende', $enigme_id);

    echo '<div class="galerie-enigme-wrapper">';
    foreach ($valid_images as $index => $image_id) {
        $alt = trim((string) get_post_meta($image_id, '_wp_attachment_image_alt', true));
        if (!$alt) {
            $alt = $image_id === ID_IMAGE_PLACEHOLDER_ENIGME
                ? __('Image par défaut de l’énigme', 'chassesautresor-com')
                : ($caption ?: __('Visuel énigme', 'chassesautresor-com'));
        }

        $classes = 'enigme-image--limited';
        if ($index === 0) {
            $classes .= ' image-active';
        }

        $attrs = [
            'class' => $classes,
            'style' => 'width:auto;max-width:100%;',
        ];

        if ($index === 0) {
            $attrs['id'] = 'image-enigme-active';
        }

        echo '<figure class="image-principale">';
        echo build_picture_enigme($image_id, $alt, ['full'], $attrs);
        echo '</figure>';
    }
    echo '</div>';
}


/**
 * Renvoie l’URL de l’image principale d’une énigme,
 * ou un placeholder si aucune image n’est définie.
 *
 * @param int $post_id
 * @param string $size
 * @return string|null
 */
function get_image_enigme(int $post_id, string $size = 'medium'): ?string
{
    $images = get_field('enigme_visuel_image', $post_id);

    if (is_array($images) && !empty($images[0]['ID'])) {
        return wp_get_attachment_image_url($images[0]['ID'], $size);
    }

    // 🧩 Placeholder image : image statique ou ID définie par toi
    return wp_get_attachment_image_url(3925, $size);
}


/**
 * Vérifie si l’énigme a une image définie.
 *
 * @param int $post_id ID du post de type énigme
 * @return bool True si l’énigme a une image, false sinon.
 */
function enigme_a_une_image(int $post_id): bool
{
    $images = get_field('enigme_visuel_image', $post_id);
    return is_array($images) && !empty($images[0]['ID']);
}



/**
 * Retourne l'URL proxy pour une vignette d’énigme à la taille souhaitée.
 *
 * @param int $enigme_id
 * @param string $taille  Taille WordPress (ex: 'thumbnail', 'medium', 'full')
 * @return string|null
 */
function get_url_vignette_enigme(int $enigme_id, string $taille = 'thumbnail'): ?string
{
    if (!utilisateur_peut_voir_enigme($enigme_id)) {
        return null;
    }

    $images = get_field('enigme_visuel_image', $enigme_id, false);
    if (!$images || !is_array($images)) {
        return null;
    }

    $image_id = $images[0] ?? null; // on récupère l’ID brut directement
    if (!$image_id) return null;

    return esc_url(add_query_arg([
        'id'     => $image_id,
        'taille' => $taille,
    ], site_url('/voir-image-enigme')));
}


/**
 * Affiche un bloc <picture> responsive pour une énigme.
 *
 * Génère un élément <picture> HTML avec différentes sources pour les tailles d’image spécifiées,
 * en utilisant le proxy /voir-image-enigme. Si aucune image n’est définie, utilise le placeholder.
 *
 * @param int    $enigme_id  ID de l’énigme concernée.
 * @param string $alt        Texte alternatif pour l’image.
 * @param array  $sizes      Liste des tailles WordPress à inclure (ordre croissant).
 * @return void
 */
function afficher_picture_vignette_enigme(int $enigme_id, string $alt = '', array $sizes = ['thumbnail', 'medium']): void
{
    $images = get_field('enigme_visuel_image', $enigme_id, false);
    $image_id = (is_array($images) && !empty($images[0])) ? (int) $images[0] : null;

    if (!$image_id) {
        echo '<div class="enigme-placeholder placeholder-svg">';
        echo file_get_contents(get_stylesheet_directory() . '/assets/svg/creation-enigme.svg');
        echo '</div>';
        return;
    }

    echo build_picture_enigme($image_id, $alt, $sizes);
}



/**
 * Retourne le chemin absolu (serveur) et le type MIME d’une image à une taille donnée.
 * Si une version WebP existe pour cette taille, elle est priorisée.
 *
 * @param int $image_id ID de l’image WordPress
 * @param string $taille Taille WordPress demandée (ex: 'thumbnail', 'medium', 'full')
 * @return array|null Tableau ['path' => string, 'mime' => string] ou null si introuvable
 */
function trouver_chemin_image(int $image_id, string $taille = 'full'): ?array
{
    $wp_size = $taille === 'full' ? [1920, 1920] : $taille;
    $src     = wp_get_attachment_image_src($image_id, $wp_size);
    $url = $src[0] ?? null;
    if (!$url) return null;

    $upload_dir = wp_get_upload_dir();
    $path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);

    // 🔁 Si une version .webp existe, on la préfère
    $webp_path = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $path);
    if ($webp_path !== $path && file_exists($webp_path)) {
        return ['path' => $webp_path, 'mime' => 'image/webp'];
    }

    // 🔁 Sinon, on vérifie le fichier d’origine
    if (file_exists($path)) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            default       => 'application/octet-stream',
        };
        return ['path' => $path, 'mime' => $mime];
    }

    return null;
}



/**
 * 🔍 get_mapping_visuel_enigme() — Retourne les infos visuelles d'une énigme selon son CTA.
 *
 * @param int $enigme_id ID de l'énigme.
 * @return array{
 *     cta: string,
 *     image_reelle: bool,
 *     fallback_svg: ?string,
 *     filtre: ?string,
 *     texte_filtre: ?string,
 *     sens: string
 * }
 */
function get_mapping_visuel_enigme(int $enigme_id): array
{
    $cta_data     = get_cta_enigme($enigme_id);
    $cta_type     = $cta_data['type'] ?? 'erreur';
    $etat_systeme = $cta_data['etat_systeme'] ?? get_field('enigme_cache_etat_systeme', $enigme_id);


    $cle = match (true) {
        $cta_type === 'voir' => 'accessible_voir', // 👈 priorité absolue
        $cta_type === 'revoir'                      => 'revoir',
        $cta_type === 'continuer'                   => 'continuer',
        $cta_type === 'soumis'                      => 'soumis',
        $cta_type === 'terminee'                    => 'terminee',
        $cta_type === 'connexion'                   => 'connexion',
        $etat_systeme === 'accessible' && $cta_type === 'engager' => 'accessible_engager',
        $etat_systeme === 'bloquee_date'            => 'bloquee_date',
        $etat_systeme === 'bloquee_pre_requis'      => 'bloquee_pre_requis',
        $etat_systeme === 'bloquee_chasse'          => 'bloquee_chasse',
        $etat_systeme === 'invalide'                => 'invalide',
        $etat_systeme === 'cache_invalide'          => 'cache_invalide',
        default                                     => 'erreur',
    };



    $mapping = [
        'accessible_voir' => [
            'image_reelle' => true,
            'fallback_svg' => null,
            'filtre'       => null,
            'sens'         => __('L’énigme est accessible et engagée', 'chassesautresor-com'),
        ],
        'accessible_engager' => [
            'image_reelle' => true,
            'fallback_svg' => null,
            'filtre'       => 'grayscale blur-xs',
            'sens'         => __('L’énigme est ouverte, mais pas encore tentée', 'chassesautresor-com'),
        ],
        'revoir' => [
            'image_reelle' => true,
            'fallback_svg' => null,
            'filtre'       => 'blur-xs',
            'sens'         => __('Énigme déjà résolue', 'chassesautresor-com'),
        ],
        'continuer' => [
            'image_reelle' => true,
            'fallback_svg' => null,
            'filtre'       => null,
            'sens'         => __('Énigme en cours', 'chassesautresor-com'),
        ],
        'soumis' => [
            'image_reelle' => true,
            'fallback_svg' => null,
            'filtre'       => null,
            'sens'         => __('Réponse en attente de validation', 'chassesautresor-com'),
        ],
        'terminee' => [
            'image_reelle' => false,
            'fallback_svg' => 'lock.svg',
            'filtre'       => 'opacity-40',
            'sens'         => __('Énigme clôturée', 'chassesautresor-com'),
        ],
        'connexion' => [
            'image_reelle' => false,
            'fallback_svg' => 'lock.svg',
            'filtre'       => 'blur-xs',
            'sens'         => __('Connexion requise', 'chassesautresor-com'),
        ],
        'bloquee_date' => [
            'image_reelle' => false,
            'fallback_svg' => 'hourglass.svg',
            'filtre'       => 'opacity-40',
            'sens'         => __('Énigme disponible plus tard', 'chassesautresor-com'),
        ],
        'bloquee_pre_requis' => [
            'image_reelle' => false,
            'fallback_svg' => 'question.svg',
            'filtre'       => 'blur-xs',
            'sens'         => esc_html__('Pré-requis non remplis', 'chassesautresor-com'),
        ],
        'bloquee_chasse' => [
            'image_reelle' => false,
            'fallback_svg' => 'lock.svg',
            'filtre'       => 'grayscale',
            'sens'         => __('Chasse verrouillée', 'chassesautresor-com'),
        ],
        'invalide' => [
            'image_reelle' => false,
            'fallback_svg' => 'warning.svg',
            'filtre'       => 'rouge-jaune',
            'sens'         => __('Énigme mal configurée', 'chassesautresor-com'),
        ],
        'cache_invalide' => [
            'image_reelle' => false,
            'fallback_svg' => 'warning.svg',
            'filtre'       => 'opacity-20',
            'sens'         => __('Cache technique corrompu', 'chassesautresor-com'),
        ],
        'erreur' => [
            'image_reelle' => false,
            'fallback_svg' => 'warning.svg',
            'filtre'       => 'opacity-20',
            'sens'         => __('Erreur technique', 'chassesautresor-com'),
        ],
    ];

    $disponible_le = null;

    if ($cle === 'bloquee_date') {
        $timestamp = strtotime(get_field('enigme_acces_date', $enigme_id));
        if ($timestamp) {
            $disponible_le = date_i18n('d/m/Y', $timestamp);
        }
    }

    return [
        'cta'           => $cta_type,
        'etat_systeme'  => $etat_systeme,
        'image_reelle'  => $mapping[$cle]['image_reelle'] ?? false,
        'fallback_svg'  => $mapping[$cle]['fallback_svg'] ?? 'warning.svg',
        'filtre'        => $mapping[$cle]['filtre'] ?? null,
        'sens'          => $mapping[$cle]['sens'] ?? __('État inconnu', 'chassesautresor-com'),
        'disponible_le' => $disponible_le,
    ];
}
