<?php

use PHPUnit\Framework\TestCase;

class AccountProfilePresentationTest extends TestCase
{
    private const THEME = __DIR__ . '/../wp-content/themes/chassesautresor';

    public function test_profile_form_is_split_into_shell_cards(): void
    {
        $form = (string) file_get_contents(
            self::THEME . '/woocommerce/myaccount/form-edit-account.php'
        );

        $this->assertStringContainsString('account-profile__grid', $form);
        $this->assertStringContainsString('Identité', $form);
        $this->assertStringContainsString('Photo de profil', $form);
        $this->assertStringContainsString('id="upload-avatar"', $form);
        $this->assertStringContainsString('id="remove-avatar"', $form);
        $this->assertStringContainsString('class="profile-avatar"', $form);
        $this->assertStringContainsString('name="account_first_name"', $form);
        $this->assertStringContainsString('name="account_email"', $form);
        $this->assertStringContainsString('name="password_current"', $form);
        $this->assertStringContainsString('name="password_1"', $form);
        $this->assertStringContainsString('name="password_2"', $form);
        $this->assertStringContainsString("'save_account_details'", $form);
        $this->assertStringContainsString('account-panel--disclosure', $form);
        $this->assertStringContainsString('Nom visible dans le compte.', $form);
        $this->assertStringContainsString('Dernière connexion le %s', $form);
        $this->assertStringContainsString('afficher_commandes_utilisateur((int) $user->ID, 3)', $form);
        $this->assertStringContainsString('Aucune commande pour le moment.', $form);
        $this->assertStringNotContainsString('dashboard-placeholder', $form);
        $this->assertLessThan(
            strpos($form, 'Aucune commande pour le moment.'),
            strpos($form, 'Voir toutes les commandes')
        );
    }

    public function test_identical_addresses_share_one_card(): void
    {
        $template = (string) file_get_contents(
            self::THEME . '/woocommerce/myaccount/my-address.php'
        );

        $this->assertStringContainsString('myaccount_addresses_match', $template);
        $this->assertStringContainsString("'Adresse'", $template);
        $this->assertStringContainsString('Facturation et livraison identiques.', $template);
        $this->assertStringContainsString('Utiliser une adresse de livraison différente', $template);
        $this->assertStringContainsString('Aucune adresse enregistrée.', $template);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_address_comparison_ignores_markup_and_blank_values(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }
        if (!function_exists('add_action')) {
            eval('function add_action($hook, $callback, $priority = 10, $accepted_args = 1){}');
        }
        if (!function_exists('add_filter')) {
            eval('function add_filter($hook, $callback, $priority = 10, $accepted_args = 1){}');
        }
        if (!class_exists('WP_User')) {
            eval('class WP_User { public $ID = 0; public $roles = []; }');
        }

        require_once self::THEME . '/inc/myaccount-functions.php';

        $this->assertTrue(myaccount_addresses_match(
            'Laurent PANNETIER<br>2045 chemin du riou<br>06140 VENCE',
            "Laurent PANNETIER<br/>2045 chemin du riou\n06140   VENCE"
        ));
        $this->assertTrue(myaccount_addresses_match('', '   '));
        $this->assertFalse(myaccount_addresses_match('06140 VENCE', ''));
        $this->assertFalse(myaccount_addresses_match('VENCE', 'NICE'));
    }

    public function test_profile_styles_use_shell_tokens(): void
    {
        $scss = (string) file_get_contents(self::THEME . '/assets/scss/_mon-compte.scss');
        $shell = (string) file_get_contents(self::THEME . '/assets/scss/_app-shell.scss');

        $this->assertStringContainsString('color: var(--shell-muted);', $scss);
        $this->assertStringContainsString('background: var(--shell-primary-button-bg);', $scss);
        $this->assertStringContainsString('color: var(--shell-primary-button-text);', $scss);
        $this->assertStringContainsString('.account-avatar', $scss);
        $this->assertStringContainsString('.account-avatar .profile-avatar img.avatar-pirate', $scss);
        $this->assertStringContainsString(
            '.account-button.account-avatar__remove[hidden]',
            $scss
        );
        $this->assertStringContainsString('var(--shell-surface)', $scss);
        $this->assertStringContainsString('.app-shell h2.dashboard-section-title', $shell);
        $this->assertStringContainsString('color: var(--shell-text);', $shell);
        $this->assertStringContainsString('--shell-card-background:', $shell);
        $this->assertStringContainsString('.app-shell .myaccount-main .dashboard-card', $shell);
        $this->assertStringContainsString('background: var(--shell-card-background);', $scss);
    }

    public function test_profile_header_drops_the_last_connection_line(): void
    {
        $layout = (string) file_get_contents(self::THEME . '/templates/myaccount/layout.php');

        $this->assertStringContainsString('myaccount_format_last_active', $layout);
        $this->assertStringContainsString('$show_last_active', $layout);
        $this->assertStringContainsString("__('Commandes', 'chassesautresor-com')", $layout);
        $this->assertStringNotContainsString('wc_last_active', $layout);
    }
}
