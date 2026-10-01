<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Decide how a riddle page behaves for a player from an already resolved context.
 */
class RiddleParticipationPolicyService {
    /** @return array{etat:string,rediriger:bool,afficher_formulaire:bool,afficher_message:bool,message_html:string} */
    public function decide(
        string $userStatus,
        bool $isAdministrator,
        bool $isDraft,
        bool $isOrganizer,
        bool $huntFinished,
        bool $isHuntEngaged,
        bool $isRiddleEngaged,
        bool $prerequisitesMet
    ): array {
        if ($isAdministrator) {
            return $this->state($userStatus, false, false);
        }
        if ($isDraft) {
            return $this->state($userStatus, true, false);
        }
        if ($isOrganizer) {
            return $this->state($userStatus, false, false);
        }
        if ($huntFinished) {
            return $this->state('terminee', false, true);
        }
        if (!$isHuntEngaged || !$isRiddleEngaged) {
            return $this->state($userStatus, true, false);
        }
        if (!$prerequisitesMet) {
            $userStatus = 'bloquee_pre_requis';
        }

        $redirect = in_array($userStatus, [
            'abandonnee',
            'bloquee_date',
            'bloquee_chasse',
            'bloquee_pre_requis',
            'invalide',
            'cache_invalide',
        ], true);

        return $this->state(
            $userStatus,
            $redirect,
            in_array($userStatus, ['en_cours', 'non_souscrite', 'echouee'], true)
        );
    }

    /** @return array{etat:string,rediriger:bool,afficher_formulaire:bool,afficher_message:bool,message_html:string} */
    private function state(string $status, bool $redirect, bool $showForm): array {
        return [
            'etat' => $status,
            'rediriger' => $redirect,
            'afficher_formulaire' => $showForm,
            'afficher_message' => false,
            'message_html' => '',
        ];
    }
}
