<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Manage temporary .htaccess suspension for protected riddle images.
 */
class RiddleImageProtectionService {
    public const PUBLIC_CACHE_CONTROL = 'public, max-age=31536000, immutable';

    public function protect(int $riddleId, bool $force = false): bool {
        if ($riddleId <= 0 || get_post_type($riddleId) !== 'enigme') {
            return false;
        }

        return $this->publishDirectory($riddleId, $force);
    }

    /**
     * Hash predictable names, then allow direct reads. A directory that still
     * contains a guessable image name keeps its current deny rules.
     */
    public function publishDirectory(int $riddleId, bool $force = false): bool {
        if ($riddleId <= 0) {
            return false;
        }

        [$protected, $temporary] = $this->paths($riddleId);
        $directory = dirname($protected);
        if (!is_dir($directory) && !wp_mkdir_p($directory)) {
            return false;
        }

        $names = new RiddleImageObscureNameService();
        $names->obscureRiddle($riddleId);
        $names->obscureDirectory($directory);
        if (!$names->riddleDirectoryIsUnpredictable($directory)) {
            return false;
        }

        $expected = $this->rules($riddleId);
        if (!$force && is_file($protected)) {
            $current = @file_get_contents($protected);
            if ($current === $expected) {
                $this->writeStepsRules($directory, $expected);

                return true;
            }
        }
        if (is_file($temporary) && !@unlink($temporary)) {
            return false;
        }

        $written = file_put_contents($protected, $expected, LOCK_EX) !== false;
        if ($written) {
            $this->writeStepsRules($directory, $expected);
        }

        return $written;
    }

    public function openStoredRiddles(): bool {
        $base = wp_upload_dir()['basedir'] . '/_enigmes';
        if (!is_dir($base)) {
            return true;
        }

        $ready = true;
        foreach ((array) glob($base . '/enigme-*') as $directory) {
            if (!is_dir($directory) || !preg_match('/enigme-(\d+)$/', $directory, $matches)) {
                continue;
            }
            if (!$this->publishDirectory((int) $matches[1], true)) {
                $ready = false;
            }
        }

        return $ready;
    }

    /** @return array{success:bool,message:string} */
    public function disable(int $riddleId): array {
        [$protected, $temporary] = $this->paths($riddleId);
        $message = 'Aucun fichier à désactiver (pas encore d’image)';
        if (file_exists($protected)) {
            if (!@rename($protected, $temporary)) {
                return ['success' => false, 'message' => 'Erreur désactivation'];
            }
            $message = 'Protection désactivée (nouveau .tmp)';
        } elseif (file_exists($temporary)) {
            $message = 'Déjà désactivé – délai renouvelé';
        }

        set_transient($this->transientKey($riddleId), time() + 180, 180);
        return ['success' => true, 'message' => $message];
    }

    public function restore(int $riddleId, bool $reinject = true): void {
        [$protected, $temporary] = $this->paths($riddleId);
        if (file_exists($temporary) && !file_exists($protected)) {
            @rename($temporary, $protected);
        } elseif (file_exists($temporary)) {
            @unlink($temporary);
        } elseif ($reinject) {
            $this->protect($riddleId, true);
        }
        delete_transient($this->transientKey($riddleId));
    }

    /** @return array{active:bool,expired:bool,timestamp:int} */
    public function getExpiration(int $riddleId): array {
        [, $temporary] = $this->paths($riddleId);
        if (!file_exists($temporary)) {
            return ['active' => false, 'expired' => false, 'timestamp' => 0];
        }
        $expiration = (int) get_transient($this->transientKey($riddleId));
        if ($expiration > time()) {
            return ['active' => true, 'expired' => false, 'timestamp' => $expiration];
        }

        $this->restore($riddleId, false);
        return ['active' => false, 'expired' => true, 'timestamp' => 0];
    }

    public function purgeExpired(): void {
        $base = wp_upload_dir()['basedir'] . '/_enigmes';
        if (!is_dir($base)) {
            return;
        }
        foreach ((array) glob($base . '/enigme-*') as $directory) {
            if (!is_dir($directory) || !preg_match('/enigme-(\d+)$/', $directory, $matches)) {
                continue;
            }
            $riddleId = (int) $matches[1];
            if ((int) get_transient($this->transientKey($riddleId)) <= time()) {
                $this->restore($riddleId, false);
            }

            [$protected, $temporary] = $this->paths($riddleId);
            // Do not recreate .htaccess while a temporary media-library disable is active.
            if (file_exists($temporary) || !file_exists($protected)) {
                continue;
            }
            $this->protect($riddleId, false);
        }
    }

    /** @return array{0:string,1:string} */
    private function paths(int $riddleId): array {
        $directory = wp_upload_dir()['basedir'] . '/_enigmes/enigme-' . $riddleId;
        $protected = $directory . '/.htaccess';
        return [$protected, $protected . '.tmp'];
    }

    private function transientKey(int $riddleId): string {
        return 'htaccess_timeout_enigme_' . $riddleId;
    }

    /**
     * Files are readable. The directory itself is not listable.
     * The file name is the cache key: it changes only when the bytes change.
     */
    public function rules(int $riddleId): string {
        $cache = self::PUBLIC_CACHE_CONTROL;

        return "# Fichiers de l'énigme {$riddleId} : lecture directe, dossier non listable.\n"
            . "Options -Indexes\n"
            . "<IfModule mod_autoindex.c>\n"
            . "IndexIgnore *\n"
            . "</IfModule>\n"
            . "<IfModule mod_headers.c>\n"
            . "<FilesMatch \"\\.(jpe?g|png|gif|webp)$\">\n"
            . "Header set Cache-Control \"{$cache}\"\n"
            . "</FilesMatch>\n"
            . "</IfModule>\n"
            . "<IfModule mod_rewrite.c>\n"
            . "RewriteEngine On\n"
            . "RewriteCond %{REQUEST_FILENAME} -d\n"
            . "RewriteRule ^ - [F,L]\n"
            . "</IfModule>\n";
    }

    private function writeStepsRules(string $directory, string $rules): void {
        $steps = $directory . '/etapes';
        if (!is_dir($steps)) {
            return;
        }

        $target = $steps . '/.htaccess';
        if (is_file($target) && @file_get_contents($target) === $rules) {
            return;
        }
        file_put_contents($target, $rules, LOCK_EX);
    }
}
