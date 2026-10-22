<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Register the administrator-managed trophy catalog. */
final class TrophyPostTypeRegistrar
{
    public const POST_TYPE = 'trophee';

    public static function register(callable $addAction): void
    {
        $addAction('init', [self::class, 'registerPostType']);
    }

    public static function registerPostType(): void
    {
        if (post_type_exists(self::POST_TYPE)) {
            unregister_post_type(self::POST_TYPE);
        }
        register_post_type(self::POST_TYPE, self::postTypeArgs());
    }

    /** @return array<string, mixed> */
    public static function postTypeArgs(): array
    {
        return [
            'labels' => self::labels(),
            'description' => __('Trophées attribués aux joueurs et aux équipes.', 'chassesautresor-com'),
            'public' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => false,
            'show_in_admin_bar' => false,
            'show_in_rest' => false,
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
            'has_archive' => false,
            'menu_icon' => 'dashicons-awards',
            'supports' => ['title'],
            'capabilities' => self::capabilities(),
            'map_meta_cap' => false,
        ];
    }

    /** @return array<string, string> */
    private static function labels(): array
    {
        return [
            'name' => __('Trophées', 'chassesautresor-com'),
            'singular_name' => __('Trophée', 'chassesautresor-com'),
            'add_new' => __('Ajouter', 'chassesautresor-com'),
            'add_new_item' => __('Ajouter un trophée', 'chassesautresor-com'),
            'edit_item' => __('Modifier le trophée', 'chassesautresor-com'),
            'new_item' => __('Nouveau trophée', 'chassesautresor-com'),
            'view_item' => __('Voir le trophée', 'chassesautresor-com'),
            'search_items' => __('Rechercher des trophées', 'chassesautresor-com'),
            'not_found' => __('Aucun trophée trouvé.', 'chassesautresor-com'),
            'not_found_in_trash' => __('Aucun trophée trouvé dans la corbeille.', 'chassesautresor-com'),
        ];
    }

    /** @return array<string, string> */
    private static function capabilities(): array
    {
        return [
            'edit_post' => 'manage_options',
            'read_post' => 'manage_options',
            'delete_post' => 'manage_options',
            'edit_posts' => 'manage_options',
            'edit_others_posts' => 'manage_options',
            'publish_posts' => 'manage_options',
            'read_private_posts' => 'manage_options',
            'delete_posts' => 'manage_options',
            'delete_private_posts' => 'manage_options',
            'delete_published_posts' => 'manage_options',
            'delete_others_posts' => 'manage_options',
            'edit_private_posts' => 'manage_options',
            'edit_published_posts' => 'manage_options',
            'create_posts' => 'manage_options',
        ];
    }
}
