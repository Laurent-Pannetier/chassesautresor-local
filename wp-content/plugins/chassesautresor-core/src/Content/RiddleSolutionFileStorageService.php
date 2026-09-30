<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Prepare the protected upload directory used by legacy riddle solution PDFs.
 */
class RiddleSolutionFileStorageService {
    private const ACCESS_CONTROL = <<<'HTACCESS'
<IfModule !authz_core_module>
Order deny,allow
Deny from all
</IfModule>
<IfModule authz_core_module>
Require all denied
</IfModule>

HTACCESS;

    /**
     * @param array<string, mixed> $directories
     * @return array<string, mixed>
     */
    public function prepareUploadDirectory(array $directories, string $contentDirectory): array {
        $protectedDirectory = rtrim($contentDirectory, '/\\') . '/protected/solutions';
        if (!is_dir($protectedDirectory) && !wp_mkdir_p($protectedDirectory)) {
            $directories['error'] = __(
                'Impossible de préparer le stockage protégé du fichier.',
                'chassesautresor-com'
            );
            return $directories;
        }

        $accessControlFile = $protectedDirectory . '/.htaccess';
        $accessControl = is_file($accessControlFile)
            ? (string) file_get_contents($accessControlFile)
            : '';
        if (!str_contains($accessControl, 'Require all denied')
            || !str_contains($accessControl, 'Deny from all')
        ) {
            $written = file_put_contents($accessControlFile, self::ACCESS_CONTROL, LOCK_EX);
            if ($written === false) {
                $directories['error'] = __(
                    'Impossible de sécuriser le stockage protégé du fichier.',
                    'chassesautresor-com'
                );
                return $directories;
            }
        }

        $directories['path'] = $protectedDirectory;
        $directories['basedir'] = $protectedDirectory;
        $directories['subdir'] = '';
        $directories['url'] = '';
        $directories['baseurl'] = '';

        return $directories;
    }
}
