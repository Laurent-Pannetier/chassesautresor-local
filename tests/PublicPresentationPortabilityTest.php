<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Presentation\FunctionalAssetManager;
use ChassesAuTresor\Core\Presentation\PublicTemplateController;
use PHPUnit\Framework\TestCase;

final class PublicPresentationPortabilityTest extends TestCase {
    private const PLUGIN = __DIR__ . '/../wp-content/plugins/chassesautresor-core';

    public function testPresentationHooksAreOwnedByThePlugin(): void {
        $filters = [];
        PublicTemplateController::register(
            static function (...$args) use (&$filters): void {
                $filters[] = $args;
            }
        );
        $actions = [];
        FunctionalAssetManager::register(
            static function (...$args) use (&$actions): void {
                $actions[] = $args;
            }
        );

        self::assertSame('template_include', $filters[0][0]);
        self::assertSame(20, $filters[0][2]);
        self::assertSame('wp_enqueue_scripts', $actions[0][0]);
    }

    public function testEveryEssentialPublicPostTypeHasAPluginFallback(): void {
        foreach (['chasse', 'enigme', 'organisateur'] as $postType) {
            $template = self::PLUGIN . '/templates/public/single-' . $postType . '.php';
            self::assertFileExists($template);
            self::assertStringContainsString('PublicViewModelFactory', (string) file_get_contents($template));
            self::assertStringContainsString('get_header()', (string) file_get_contents($template));
            self::assertStringContainsString('get_footer()', (string) file_get_contents($template));
        }
    }

    public function testFunctionalAssetsArePluginOwnedAndTheHistoricalThemeIsExcluded(): void {
        self::assertFileExists(self::PLUGIN . '/assets/css/public.css');
        self::assertFileExists(self::PLUGIN . '/assets/js/public.js');
        $manager = (string) file_get_contents(self::PLUGIN . '/src/Presentation/FunctionalAssetManager.php');
        self::assertStringContainsString("get_stylesheet() === 'chassesautresor'", $manager);
        self::assertStringContainsString("wp_enqueue_style(", $manager);
        self::assertStringContainsString("wp_enqueue_script(", $manager);
    }

    public function testPluginPresentationNeverLoadsAFileOrAssetFromTheHistoricalTheme(): void {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::PLUGIN));
        foreach ($iterator as $file) {
            if (!$file->isFile() || !in_array($file->getExtension(), ['php', 'js', 'css'], true)) {
                continue;
            }
            $source = (string) file_get_contents($file->getPathname());
            self::assertStringNotContainsString('themes/chassesautresor', $source, $file->getPathname());
            self::assertStringNotContainsString('get_theme_file_path(', $source, $file->getPathname());
        }
    }
}
