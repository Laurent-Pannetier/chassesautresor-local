<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Version ACF field groups with the plugin.
 *
 * The content types chasse, enigme, indice, organisateur and solution stay
 * registered by ACF. Saving a field group writes JSON into acf-json so the
 * model ships with this plugin. Loading prefers that directory alongside any
 * other JSON paths already configured.
 */
final class AcfLocalJsonRegistrar
{
    public static function register(callable $addFilter): void
    {
        $directory = self::directory();
        if (!is_dir($directory) && function_exists('wp_mkdir_p')) {
            wp_mkdir_p($directory);
        }

        $addFilter('acf/settings/save_json', [self::class, 'savePath']);
        $addFilter('acf/settings/load_json', [self::class, 'loadPaths']);
    }

    public static function directory(): string
    {
        return dirname(__DIR__, 2) . '/acf-json';
    }

    public static function savePath(string $path): string
    {
        return self::directory();
    }

    /**
     * @param string[] $paths
     * @return string[]
     */
    public static function loadPaths(array $paths): array
    {
        $paths[] = self::directory();

        return array_values(array_unique($paths));
    }
}
