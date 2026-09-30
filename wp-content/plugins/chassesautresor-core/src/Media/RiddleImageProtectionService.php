<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Manage temporary .htaccess suspension for protected riddle images.
 */
class RiddleImageProtectionService {
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
            do_action('chassesautresor_reinject_riddle_image_protection', $riddleId);
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
}
