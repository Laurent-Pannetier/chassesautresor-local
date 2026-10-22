<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Auth\SocialAuthRedirect;
use ChassesAuTresor\Core\Auth\SocialAuthService;
use ChassesAuTresor\Core\Auth\SocialAuthSettings;

/**
 * URL publiques utilisées par le thème et par les filtres de connexion WordPress.
 */
function cat_social_auth_callback_url(): string
{
    $structure = (string) get_option('permalink_structure');
    if ($structure === '') {
        return add_query_arg('cat_connexion', '1', home_url('/'));
    }

    return user_trailingslashit(home_url('/connexion'));
}

function cat_social_auth_page_url(string $redirect = '', string $view = ''): string
{
    $base = cat_social_auth_callback_url();
    $args = [];
    if ($view !== '') {
        $args['vue'] = $view;
    }
    if ($redirect !== '') {
        $args['redirect_to'] = (new SocialAuthRedirect())->accept($redirect, home_url('/'));
    }

    return $args === [] ? $base : add_query_arg($args, $base);
}

function cat_social_auth_is_request(): bool
{
    $value = get_query_var('cat_connexion');
    if ((string) $value === '1') {
        return true;
    }

    $request = '';
    if (isset($GLOBALS['wp']) && is_object($GLOBALS['wp']) && isset($GLOBALS['wp']->request)) {
        $request = trim((string) $GLOBALS['wp']->request, '/');
    }

    return $request === 'connexion';
}

/**
 * @return array<int, string>
 */
function cat_social_auth_ready_providers(): array
{
    $service = new SocialAuthService();
    $settings = $service->storedSettings();
    $gate = new SocialAuthSettings();
    $ready = [];
    foreach (['discord', 'google'] as $provider) {
        if ($gate->ready($settings, $provider)) {
            $ready[] = $provider;
        }
    }

    return $ready;
}

function cat_social_auth_error_message(string $code): string
{
    $messages = [
        'identifiants' => __('Identifiant ou mot de passe incorrect.', 'chassesautresor-com'),
        'state' => __('La connexion a expiré. Recommencez.', 'chassesautresor-com'),
        'provider' => __('Le service n’a pas répondu. Réessayez dans un instant.', 'chassesautresor-com'),
        'provider_id' => __('Le compte du service est incomplet.', 'chassesautresor-com'),
        'email_missing' => __(
            'Ce compte n’a pas fourni d’e-mail. Autorisez l’e-mail, ou utilisez l’autre service.',
            'chassesautresor-com'
        ),
        'email_unverified' => __(
            'L’e-mail de ce compte n’est pas vérifié. Vérifiez-le, puis réessayez.',
            'chassesautresor-com'
        ),
        'session_conflict' => __(
            'Ce service est déjà relié à un autre compte du site.',
            'chassesautresor-com'
        ),
        'cgu' => __('Acceptez les conditions pour créer le compte.', 'chassesautresor-com'),
        'inscription' => __('Le compte n’a pas pu être créé.', 'chassesautresor-com'),
        'inscription_fermee' => __(
            'La création de compte par e-mail est fermée.',
            'chassesautresor-com'
        ),
        'email_invalide' => __('Indiquez une adresse e-mail valide.', 'chassesautresor-com'),
        'email_existe' => __(
            'Un compte existe déjà avec cet e-mail. Connectez-vous.',
            'chassesautresor-com'
        ),
        'lien' => __('Mot de passe incorrect.', 'chassesautresor-com'),
        'lien_expire' => __('La liaison a expiré. Recommencez la connexion.', 'chassesautresor-com'),
    ];

    return $messages[$code] ?? '';
}

function cat_social_auth_info_message(string $code): string
{
    $messages = [
        'email_envoye' => __(
            'Si un compte correspond, un e-mail vient d’être envoyé.',
            'chassesautresor-com'
        ),
        'inscription_email' => __(
            'Consultez votre e-mail pour définir votre mot de passe.',
            'chassesautresor-com'
        ),
        'social_discord' => __(
            'Ce compte utilise Discord. Revenez à Discord, ou demandez un e-mail pour choisir un mot de passe.',
            'chassesautresor-com'
        ),
        'social_google' => __(
            'Ce compte utilise Google. Revenez à Google, ou demandez un e-mail pour choisir un mot de passe.',
            'chassesautresor-com'
        ),
        'social_both' => __(
            'Ce compte utilise Discord ou Google, ou un e-mail pour choisir un mot de passe.',
            'chassesautresor-com'
        ),
    ];

    return $messages[$code] ?? '';
}
