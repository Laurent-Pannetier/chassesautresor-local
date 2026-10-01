<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Relationships\OrganizerCtaDecisionService;

if (!function_exists('get_cta_devenir_organisateur')) {
    /** @return array{label:string,url:?string,disabled:bool} */
    function get_cta_devenir_organisateur(?int $userId = null): array {
        $userId = $userId ?: get_current_user_id();
        $label = __('Créer mon profil', 'chassesautresor-com');
        $url = home_url('/creer-mon-profil/');
        $disabled = false;

        if ($userId <= 0) {
            return [
                'label' => __('Devenir organisateur', 'chassesautresor-com'),
                'url' => wp_login_url($url),
                'disabled' => false,
            ];
        }

        $user = get_userdata($userId);
        if (!$user) {
            return compact('label', 'url', 'disabled');
        }

        $profileState = cat_is_user_profile_complete($userId);
        if (!$profileState['complete']) {
            $profileUrl = function_exists('wc_get_account_endpoint_url')
                ? wc_get_account_endpoint_url('edit-account')
                : home_url('/mon-compte/edit-account/');
            add_site_message(
                'error',
                cat_get_missing_profile_fields_message($profileState['missing']),
                false,
                'profil_incomplet_' . $userId
            );

            return [
                'label' => __('Compléter mon profil', 'chassesautresor-com'),
                'url' => $profileUrl,
                'disabled' => false,
            ];
        }

        remove_site_message('profil_incomplet_' . $userId);
        myaccount_remove_persistent_message($userId, 'profil_incomplet');
        $requestStatus = cat_get_organisateur_request_status($userId);
        if ($requestStatus['expired']) {
            add_site_message(
                'info',
                __(
                    'Votre précédente demande de création de profil a expiré. Vous pouvez en envoyer une nouvelle.',
                    'chassesautresor-com'
                ),
                false,
                'profil_expire_' . $userId
            );
        }

        $organizerId = (int) get_organisateur_from_user($userId);
        $hasPendingHunt = false;
        if ($organizerId > 0) {
            $query = get_chasses_de_organisateur($organizerId);
            foreach (($query && $query->have_posts()) ? $query->posts : [] as $huntId) {
                if (get_field('chasse_cache_statut_validation', (int) $huntId) === 'en_attente') {
                    $hasPendingHunt = true;
                    break;
                }
            }
        }

        $roles = (array) $user->roles;
        $organizerRole = defined('ROLE_ORGANISATEUR') ? ROLE_ORGANISATEUR : 'organisateur';
        $creationRole = defined('ROLE_ORGANISATEUR_CREATION')
            ? ROLE_ORGANISATEUR_CREATION
            : 'organisateur_creation';
        $decision = (new OrganizerCtaDecisionService())->decide(
            in_array('administrator', $roles, true),
            !empty($requestStatus['token']),
            in_array($creationRole, $roles, true),
            in_array($organizerRole, $roles, true),
            $organizerId,
            $hasPendingHunt
        );

        $views = [
            'administrator' => [__('Salut Patron', 'chassesautresor-com'), null, true],
            'resend_confirmation' => [
                __('Renvoyer l’email de confirmation', 'chassesautresor-com'),
                home_url('/creer-mon-profil/?resend=1'),
                false,
            ],
            'resend_pending_hunt' => [
                __('Renvoyer l’email', 'chassesautresor-com'),
                home_url('/creer-mon-profil/?resend=1'),
                false,
            ],
            'profile' => [__('Votre profil', 'chassesautresor-com'), get_permalink($organizerId), false],
            'apply' => [__('Devenir organisateur', 'chassesautresor-com'), $url, false],
            'create' => [$label, $url, $disabled],
        ];
        [$label, $url, $disabled] = $views[$decision];

        return compact('label', 'url', 'disabled');
    }
}
