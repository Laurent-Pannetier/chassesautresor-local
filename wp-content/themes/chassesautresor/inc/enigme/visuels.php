<?php
defined('ABSPATH') || exit;

// ==================================================
// 🖼️ AFFICHAGE DES VISUELS D’ÉNIGMES
/**
 * 🔹 define('ID_IMAGE_PLACEHOLDER_ENIGME', 3925) → Définit l’identifiant de l’image placeholder utilisée pour les énigmes.
 * 🔹 afficher_visuels_enigme() → Affiche la galerie visuelle de l’énigme si l’utilisateur y a droit (image principale + vignettes).
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
    for ($i = count($used_sizes) - 1; $i > 0; $i--) {
        $size = $used_sizes[$i];
        $src = esc_url(add_query_arg([
            'id'     => $image_id,
            'taille' => $size,
        ], $base_url));
        $media = $breakpoints[$size];
        $media_attr = $media ? ' media="' . $media . '"' : '';
        $html .= '  <source srcset="' . $src . '" data-size="' . $size . '"' . $media_attr . ">\n";
    }

    $fallback_size = $used_sizes[0];
    $src_fallback = esc_url(add_query_arg([
        'id'     => $image_id,
        'taille' => $fallback_size,
    ], $base_url));

    $attr_str = '';
    foreach ($img_attrs as $key => $value) {
        $attr_str .= ' ' . $key . '="' . esc_attr($value) . '"';
    }

    $html .= '  <img src="' . $src_fallback . '" alt="' . esc_attr($alt) . '" loading="lazy"' . $attr_str . ">\n";
    $html .= "</picture>\n";

    return $html;
}

/**
 * Affiche une galerie d’images d’une énigme si l’utilisateur y a droit.
 *
 * Compatible Fancybox 3 (ancien Firelight/Easy Fancybox) via `rel="lightbox-enigme"`.
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
    if (!$images || !is_array($images)) return;

    echo '<div class="galerie-enigme-wrapper">';

    // 📸 Image principale
    $image_id_active = $images[0]['ID'] ?? null;
    if ($image_id_active) {
        $href_full = add_query_arg([
            'id'     => $image_id_active,
            'taille' => 'full',
        ], site_url('/voir-image-enigme'));

        echo '<div class="image-principale">';
        echo '<a href="' . esc_url($href_full) . '" class="fancybox image" rel="lightbox-enigme">';
        echo build_picture_enigme($image_id_active, __('Visuel énigme', 'chassesautresor-com'), ['full'], [
            'id'    => 'image-enigme-active',
            'class' => 'image-active',
        ]);
        echo '</a>';
        echo '</div>';
    }


    // 🖼️ Vignettes + liens lightbox
    if (count($images) > 1) {
        echo '<div class="galerie-vignettes">';
        foreach ($images as $index => $image) {
            $img_id = $image['ID'] ?? null;
            if (!$img_id) continue;

            $src_thumb = esc_url(add_query_arg([
                'id' => $img_id,
                'taille' => 'thumbnail',
            ], site_url('/voir-image-enigme')));

            $src_full = esc_url(add_query_arg('id', $img_id, site_url('/voir-image-enigme')));

            $class = 'vignette' . ($index === 0 ? ' active' : '');

            echo '<img src="' . $src_thumb . '" class="' . esc_attr($class) . '" alt="" data-image-id="' . esc_attr($img_id) . '">';
            echo '<a href="' . $src_full . '" rel="lightbox-enigme" class="fancybox hidden-lightbox-link" style="display:none;"></a>';
        }
        echo '</div>';
    }

    // 🔁 JS interaction
?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const vignettes = document.querySelectorAll('.vignette');
            const principale = document.getElementById('image-enigme-active');
            const lien = principale?.closest('a');
            const container = principale?.closest('.image-principale');
            const picture = principale?.parentElement;

            vignettes.forEach(v => {
                v.addEventListener('click', () => {
                    const id = v.getAttribute('data-image-id');
                    if (!id || !principale || !lien || !picture) return;

                    const base = '/voir-image-enigme?id=' + id;

                    if (container) {
                        container.style.minHeight = container.offsetHeight + 'px';
                    }

                    const preload = new Image();
                    preload.onload = () => {
                        picture.querySelectorAll('source').forEach(source => {
                            const size = source.getAttribute('data-size');
                            source.srcset = base + '&taille=' + size;
                        });

                        principale.src = base + '&taille=thumbnail';
                        lien.href = base + '&taille=full';

                        if (container) {
                            container.style.minHeight = '';
                        }

                        vignettes.forEach(x => x.classList.remove('active'));
                        v.classList.add('active');
                    };

                    preload.src = base + '&taille=thumbnail';
                });
            });
        });
    </script>
<?php
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
    $src = wp_get_attachment_image_src($image_id, $taille);
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
            'sens'         => "L’énigme est accessible et engagée",
        ],
        'accessible_engager' => [
            'image_reelle' => true,
            'fallback_svg' => null,
            'filtre'       => 'grayscale blur-xs',
            'sens'         => "L’énigme est ouverte, mais pas encore tentée",
        ],
        'revoir' => [
            'image_reelle' => true,
            'fallback_svg' => null,
            'filtre'       => 'blur-xs',
            'sens'         => "Énigme déjà résolue",
        ],
        'continuer' => [
            'image_reelle' => true,
            'fallback_svg' => null,
            'filtre'       => null,
            'sens'         => "Énigme en cours",
        ],
        'soumis' => [
            'image_reelle' => true,
            'fallback_svg' => null,
            'filtre'       => null,
            'sens'         => "Réponse en attente de validation",
        ],
        'terminee' => [
            'image_reelle' => false,
            'fallback_svg' => 'lock.svg',
            'filtre'       => 'opacity-40',
            'sens'         => "Énigme clôturée",
        ],
        'connexion' => [
            'image_reelle' => false,
            'fallback_svg' => 'lock.svg',
            'filtre'       => 'blur-xs',
            'sens'         => "Connexion requise",
        ],
        'bloquee_date' => [
            'image_reelle' => false,
            'fallback_svg' => 'hourglass.svg',
            'filtre'       => 'opacity-40',
            'sens'         => "Énigme disponible plus tard",
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
            'sens'         => "Chasse verrouillée",
        ],
        'invalide' => [
            'image_reelle' => false,
            'fallback_svg' => 'warning.svg',
            'filtre'       => 'rouge-jaune',
            'sens'         => "Énigme mal configurée",
        ],
        'cache_invalide' => [
            'image_reelle' => false,
            'fallback_svg' => 'warning.svg',
            'filtre'       => 'opacity-20',
            'sens'         => "Cache technique corrompu",
        ],
        'erreur' => [
            'image_reelle' => false,
            'fallback_svg' => 'warning.svg',
            'filtre'       => 'opacity-20',
            'sens'         => "Erreur technique",
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
        'sens'          => $mapping[$cle]['sens'] ?? 'État inconnu',
        'disponible_le' => $disponible_le,
    ];
}
