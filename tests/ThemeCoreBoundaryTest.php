<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ThemeCoreBoundaryTest extends TestCase
{
    private const THEME_PATH = __DIR__ . '/../wp-content/themes/chassesautresor';

    public function testThemeDoesNotLoadCoreImplementationFiles(): void
    {
        $violations = $this->findPhpMatches(
            '/plugins\/chassesautresor-core\/src|chassesautresor-core\/src/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotRegisterAjaxEndpoints(): void
    {
        $violations = $this->findPhpMatches(
            '/add_action\s*\(\s*[\'\"]wp_ajax_(?:nopriv_)?/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotConstructCoreRepositories(): void
    {
        $violations = $this->findPhpMatches(
            '/new\s+(?:\\?ChassesAuTresor\\Core\\[^;()]+\\)?[A-Za-z]+Repository\s*\(/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotOwnPluginLifecycleOrBusinessMutationHooks(): void
    {
        $violations = $this->findPhpMatches(
            '/add_action\s*\(\s*[\'\"](?:after_switch_theme|woocommerce_thankyou)[\'\"]/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testLegacyHuntOrganizerAssignmentStaysOutOfTheme(): void
    {
        $violations = $this->findPhpMatches('/assigner_organisateur_automatiquement/');

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testLegacyStatisticsResetWorkflowStaysOutOfTheme(): void
    {
        $violations = $this->findPhpMatches(
            '/admin_post_(?:reset_stats_action|toggle_reinit_stats_action)|'
            . 'traiter_reinitialisation_stats|supprimer_metas_(?:utilisateur|organisateur|globales|post)/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotOwnConversionSettingsEntryPoints(): void
    {
        $violations = $this->findPhpMatches(
            '/init_taux_conversion|traiter_mise_a_jour_taux_conversion|traiter_demande_paiement|'
            . 'traiter_gestion_points/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDelegatesHuntModerationPolicyToCore(): void
    {
        $path = self::THEME_PATH . '/inc/admin-functions.php';
        $contents = (string) file_get_contents($path);

        self::assertStringContainsString('HuntModerationService', $contents);
    }

    public function testRemovedCompatibilityLoadersStayRemoved(): void
    {
        $loaders = [
            self::THEME_PATH . '/inc/PointsRepository.php',
            self::THEME_PATH . '/inc/messages/class-user-message-repository.php',
            self::THEME_PATH . '/inc/cli/class-cat-cli-command.php',
        ];

        foreach ($loaders as $loader) {
            self::assertFileDoesNotExist($loader);
        }
    }

    /** @return string[] */
    private function findPhpMatches(string $pattern): array
    {
        $violations = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::THEME_PATH, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            if (strpos($path, '/tests/') !== false) {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());
            if (preg_match($pattern, $contents) === 1) {
                $violations[] = str_replace(str_replace('\\', '/', self::THEME_PATH) . '/', '', $path);
            }
        }

        sort($violations);
        return $violations;
    }

    /** @param string[] $violations */
    private function formatViolations(array $violations): string
    {
        return $violations === []
            ? ''
            : "Theme/core boundary violations:\n- " . implode("\n- ", $violations);
    }
}
