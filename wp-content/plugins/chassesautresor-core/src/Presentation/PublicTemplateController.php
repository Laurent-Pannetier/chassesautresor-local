<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Presentation;

/** Supply public CPT templates only when the active theme has no dedicated template. */
final class PublicTemplateController {
    private const POST_TYPES = ['chasse', 'enigme', 'organisateur'];

    public static function register(callable $addFilter): void {
        $addFilter('template_include', [self::class, 'filter'], 20);
    }

    public static function filter(string $template): string {
        if (get_stylesheet() === 'chassesautresor') {
            return $template;
        }

        if (is_post_type_archive(self::POST_TYPES)) {
            $queryPostType = get_query_var('post_type');
            $postType = is_array($queryPostType) ? (string) reset($queryPostType) : (string) $queryPostType;
            if (in_array($postType, self::POST_TYPES, true)
                && basename($template) !== 'archive-' . $postType . '.php') {
                return self::resolver()->resolve('public/archive.php');
            }
        }

        if (!is_singular(self::POST_TYPES)) {
            return $template;
        }

        $postType = (string) get_post_type();
        if (basename($template) === 'single-' . $postType . '.php') {
            return $template;
        }

        return self::resolver()->resolve('public/single-' . $postType . '.php');
    }

    public static function resolver(): TemplateResolver {
        return new TemplateResolver(dirname(__DIR__, 2) . '/templates');
    }
}
