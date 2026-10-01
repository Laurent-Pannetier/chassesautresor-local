<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

class AccountSectionAccessService {
    /** @return array{error:?string,status:int,template:string} */
    public function resolve(bool $loggedIn, string $section, bool $administrator): array {
        if (!$loggedIn) {
            return ['error' => 'unauthorized', 'status' => 403, 'template' => ''];
        }

        $templates = [
            'organisateurs' => 'content-organisateurs.php',
            'statistiques' => 'content-statistiques.php',
            'outils' => 'content-outils.php',
        ];
        if (!isset($templates[$section])) {
            return ['error' => 'not_found', 'status' => 404, 'template' => ''];
        }
        if (!$administrator) {
            return ['error' => 'unauthorized', 'status' => 403, 'template' => ''];
        }

        return ['error' => null, 'status' => 200, 'template' => $templates[$section]];
    }
}
