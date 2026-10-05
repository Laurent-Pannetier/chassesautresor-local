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

        $expected = $this->rules($riddleId);
        if (!$force && file_exists($protected)) {
            $current = @file_get_contents($protected);
            if ($current === $expected) {
                return true;
            }
        }
        if (file_exists($temporary) && !@unlink($temporary)) {
            return false;
        }

        return file_put_contents($protected, $expected, LOCK_EX) !== false;
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
     * Block direct HTTP access while allowing LiteSpeed X-LiteSpeed-Location.
     *
     * LiteSpeed keeps %{ORG_REQ_URI} as the client-facing URI. A request that started
     * as /voir-image-enigme can therefore internally fetch /_enigmes/... without matching
     * the deny rule. Require all denied would also block that internal redirect.
     */
    public function rules(int $riddleId): string {
        return "# Protection des images de l'énigme {$riddleId}\n"
            . "<IfModule mod_rewrite.c>\nRewriteEngine On\n\n"
            . "# LiteSpeed: refuse les accès directs à /_enigmes/ (ORG_REQ_URI = URI d'origine)\n"
            . "RewriteCond %{ORG_REQ_URI} /_enigmes/\n"
            . "RewriteCond %{HTTP_REFERER} !^https?://[^/]+/wp-admin/ [NC]\n"
            . "RewriteRule \\.(jpe?g|png|gif|webp)$ - [F,L]\n\n"
            . "# Apache / Local: ORG_REQ_URI absent → refuse l'accès HTTP direct aux images\n"
            . "RewriteCond %{ORG_REQ_URI} ^$\n"
            . "RewriteCond %{HTTP_REFERER} !^https?://[^/]+/wp-admin/ [NC]\n"
            . "RewriteRule \\.(jpe?g|png|gif|webp)$ - [F,L]\n"
            . "</IfModule>\n";
    }
}
