<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Register the private content type used for intermediate riddle steps. */
final class RiddleStepPostTypeRegistrar {
    public const POST_TYPE = 'enigme_etape';

    public static function register(callable $addAction): void {
        $addAction('init', [self::class, 'registerPostType']);
    }

    public static function registerPostType(): void {
        register_post_type(self::POST_TYPE, self::postTypeArgs());
    }

    /** @return array<string, mixed> */
    public static function postTypeArgs(): array {
        return [
            'labels' => self::labels(),
            'description' => __('Étapes intermédiaires associées aux énigmes.', 'chassesautresor-com'),
            'public' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_nav_menus' => false,
            'show_in_admin_bar' => false,
            'show_in_rest' => false,
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
            'has_archive' => false,
            'supports' => ['title', 'author', 'page-attributes'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ];
    }

    /** @return array<string, string> */
    private static function labels(): array {
        return [
            'name' => __('Étapes d’énigme', 'chassesautresor-com'),
            'singular_name' => __('Étape d’énigme', 'chassesautresor-com'),
            'add_new' => __('Ajouter', 'chassesautresor-com'),
            'add_new_item' => __('Ajouter une étape', 'chassesautresor-com'),
            'edit_item' => __('Modifier l’étape', 'chassesautresor-com'),
            'new_item' => __('Nouvelle étape', 'chassesautresor-com'),
            'view_item' => __('Voir l’étape', 'chassesautresor-com'),
            'search_items' => __('Rechercher des étapes', 'chassesautresor-com'),
            'not_found' => __('Aucune étape trouvée.', 'chassesautresor-com'),
            'not_found_in_trash' => __('Aucune étape trouvée dans la corbeille.', 'chassesautresor-com'),
        ];
    }
}
