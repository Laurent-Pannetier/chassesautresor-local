<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Site;

/**
 * Décide qui peut ouvrir les énigmes de la chasse principale.
 *
 * La règle ne s'applique qu'à une chasse jouable (publiée, validée, en cours, gratuite).
 * Administrateur et organisateur associé restent autorisés. Les autres statuts
 * (à venir, terminée, payante, démo non publiée) conservent l'accès actuel.
 */
final class PlayerEnigmasAccessPolicy
{
    public const ALLOW = 'allow';
    public const LOGIN = 'login';
    public const HOME = 'home';

    public function isPlayable(
        string $publicationStatus,
        string $validationStatus,
        string $businessStatus,
        int $cost
    ): bool {
        return $publicationStatus === 'publish'
            && $validationStatus === 'valide'
            && in_array($businessStatus, ['en_cours', 'payante'], true)
            && $cost <= 0;
    }

    public function destination(
        bool $singleHuntMode,
        bool $isPrimaryHunt,
        bool $playable,
        bool $privileged,
        bool $loggedIn,
        bool $engaged
    ): string {
        if (!$singleHuntMode || !$isPrimaryHunt || !$playable || $privileged) {
            return self::ALLOW;
        }

        if (!$loggedIn) {
            return self::LOGIN;
        }

        if (!$engaged) {
            return self::HOME;
        }

        return self::ALLOW;
    }

    public function showMenu(string $destination): bool
    {
        return $destination === self::ALLOW;
    }
}
