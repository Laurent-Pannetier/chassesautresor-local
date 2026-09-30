<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Manage temporary .htaccess suspension for protected riddle images.
 */
class RiddleImageProtectionService {
    public function protect(int $riddleId, bool $force = false): bool {
        if ($riddleId <= 0 || get_post_type($riddleId) !== 'enigme') {
            return false;
        }

        [$protected, $temporary] = $this->paths($riddleId);
        $directory = dirname($protected);
        if (!is_dir($directory) && !wp_mkdir_p($directory)) {
            return false;
        }
        if (!$force && file_exists($protected)) {
            return true;
        }
        if (file_exists($temporary) && !@unlink($temporary)) {
            return false;
        }

        return file_put_contents($protected, $this->rules($riddleId), LOCK_EX) !== false;
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

    private function rules(int $riddleId): string {
        return "# Protection des images de l'énigme {$riddleId}\n"
            . "<IfModule mod_rewrite.c>\nRewriteEngine On\n\n"
            . "# Autorise uniquement l’accès depuis l’administration WordPress\n"
            . "RewriteCond %{REQUEST_URI} ^/wp-admin/ [OR]\n"
            . "RewriteCond %{HTTP_REFERER} ^(/wp-admin/|https?://[^/]+/wp-admin/) [NC]\n"
            . "RewriteRule . - [L]\n\n# Blocage par défaut\n"
            . "<FilesMatch \"\\.(jpg|jpeg|png|gif|webp)$\">\n"
            . "  Require all denied\n</FilesMatch>\n</IfModule>";
    }
}
